// Copyright 2019 Google LLC.
//
// Licensed under the Apache License, Version 2.0 (the "License");
// you may not use this file except in compliance with the License.
// You may obtain a copy of the License at
//
//     http://www.apache.org/licenses/LICENSE-2.0
//
// Unless required by applicable law or agreed to in writing, software
// distributed under the License is distributed on an "AS IS" BASIS,
// WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
// See the License for the specific language governing permissions and
// limitations under the License.


// The converter command converts function code located at /workspace to a PHP application, in-place.
// It treats supplied source directory as a Composer project and installs Invoker package as a dependency,
// converting it to a working PHP application with router located at vendor/google/function-invoker/router.php.
// To build a Knative-compatible function converter image, run the following command from the project root:
// $ docker build -t <tag> -f builders/javascript/converter/Dockerfile .
package main

import (
	"bytes"
	"encoding/json"
	"errors"
	"fmt"
	"io/ioutil"
	"log"
	"os"
	"os/exec"
	"path"
)

func writeManifest(manifest map[string]interface{}) error {
	manifestBytes, err := json.Marshal(manifest)
	if err != nil {
		return err
	}
	return ioutil.WriteFile("composer.json", manifestBytes, 0644)
}

func addCustomRepositories() error {
	customRepositories := []interface{}{
		map[string]interface{}{
			"type": "path",
			"url":  "../invoker",
			"options": map[string]interface{}{
				"symlink": false,
			},
		},
	}

	manifestBytes, err := ioutil.ReadFile("composer.json")
	if err != nil {
		if !os.IsNotExist(err) {
			return err
		}
		return writeManifest(map[string]interface{}{"repositories": customRepositories})
	}

	var manifest map[string]interface{}
	if err := json.Unmarshal(manifestBytes, &manifest); err != nil {
		return err
	}
	rawRepositories, hasRepositories := manifest["repositories"]
	if !hasRepositories {
		manifest["repositories"] = customRepositories
		return writeManifest(manifest)
	}
	repositories, ok := rawRepositories.([]interface{})
	if !ok {
		return errors.New("repositories field of composer.json must be a list")
	}
	manifest["repositories"] = append(repositories, customRepositories...)
	return writeManifest(manifest)
}

func moveToSubdirectory(name string) error {
	userFiles, err := ioutil.ReadDir(".")
	if err != nil {
		return err
	}
	tmpLocation, err := ioutil.TempDir(".", name)
	if err != nil {
		return err
	}
	for _, file := range userFiles {
		if err := os.Rename(file.Name(), path.Join(tmpLocation, file.Name())); err != nil {
			return err
		}
	}
	return os.Rename(tmpLocation, name)
}

func main() {
	fmt.Println("Converting PHP function to application...")

	if err := addCustomRepositories(); err != nil {
		log.Fatalf("Error: could not add custom repositories: %v", err)
	}
	if err := moveToSubdirectory("app"); err != nil {
		log.Fatalf("Error: could not move user code to new location: %v", err)
	}
	if err := exec.Command("/bin/cp", "-r", "/invoker", "invoker").Run(); err != nil {
		log.Fatalf("Error: could not copy Function Invoker: %v", err)
	}

	os.Chdir("app")
	// TODO: raise an error if Invoker is already a direct dependency of user project,
	// not to cause a silent update.
	cmd := exec.Command("/usr/local/bin/composer", "-n", "require", "google/function-invoker")
	cmd.Stderr = &bytes.Buffer{}
	if err := cmd.Run(); err != nil {
		_, ok := err.(*exec.ExitError)
		if !ok {
			log.Fatalf("Error: could not run Composer: %v", err)
		}
		log.Fatalf("Error: could not install Function Invoker: %v\nComposer STDERR:\n%s", err, cmd.Stderr)
	}

	fmt.Println("Successfully converted PHP function to application.")
}
