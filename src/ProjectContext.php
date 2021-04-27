<?php
/**
 * Copyright 2021 Google LLC.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *      http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Google\CloudFunctions;

use Google\Cloud\Storage\StreamWrapper;
use Google\Cloud\Storage\StorageClient;
use RuntimeException;

/**
 * @internal
 */
class ProjectContext
{
    private $documentRoot = __DIR__ . '/../../../../';

    public function locateAutoloadFile(): ?string
    {
        /**
         * Determine the autoload file to load.
         */
        if (file_exists($vendored = __DIR__ . '/../../../autoload.php')) {
            // when running from vendor/google/cloud-functions-framework
            return $vendored;
        }

        if (file_exists($cloned = __DIR__ . '/../vendor/autoload.php')) {
            // when running from git clone.
            return $cloned;
        }

        return null;
    }

    public function locateFunctionSource(?string $functionSource): ?string
    {
        // Ensure function source is loaded relative to the application root
        if ($functionSource) {
            if (0 !== strpos($functionSource, '/')) {
                // Make the path absolute
                $absoluteSource = $this->documentRoot . $functionSource;
            } else {
                $absoluteSource = $functionSource;
            }

            if (!file_exists($absoluteSource)) {
                throw new RuntimeException(sprintf(
                    'Unable to load function from "%s"',
                    $functionSource
                ));
            }

            return $absoluteSource;
        }

        if (file_exists($defaultSource = $this->documentRoot . 'index.php')) {
            // When running from vendor/google/cloud-functions-framework, default to
            // "index.php" in the root of the application.
            return $defaultSource;
        }

        // No function source found. Assume the function source is autoloaded.
        return null;
    }

    /**
     * Register the "gs://" stream wrapper for Cloud Storage if the package
     * "google/cloud-storage" is installed and the "gs" protocol has not been
     * registered.
     */
    public function registerCloudStorageStreamWrapperIfPossible()
    {
        if (class_exists(StreamWrapper::class)) {
            if (!in_array('gs', stream_get_wrappers())) {
                // Create a default GCS client and register the stream wrapper
                $storage = new StorageClient();
                StreamWrapper::register($storage);
            }
        }
    }
}
