<?php
/**
 * Copyright 2019 Google LLC.
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

use Google\CloudFunctions\Emitter;
use Google\CloudFunctions\Invoker;
use Google\CloudFunctions\ProjectContext;

// ProjectContext finds the autoload file, so we must manually include it first
require_once __DIR__ . '/src/ProjectContext.php';

$projectContext = new ProjectContext();

if ($autoloadFile = $projectContext->locateAutoloadFile()) {
    require_once $autoloadFile;
}

/**
 * Determine the function source file to load
 */
$functionSourceEnv = getenv('FUNCTION_SOURCE', true);
if ($source = $projectContext->locateFunctionSource($functionSourceEnv)) {
    require_once $source;
}

// Register the "gs://" stream wrapper for Cloud Storage if the package
// "google/cloud-storage" is installed and the "gs" protocol has not been
// registered
$projectContext->registerCloudStorageStreamWrapperIfPossible();

/**
 * Invoke the function based on the function type.
 */
(function () {
    $target = getenv('FUNCTION_TARGET', true);
    if (false === $target) {
        throw new RuntimeException('FUNCTION_TARGET is not set');
    }
    if (!is_callable($target)) {
        throw new InvalidArgumentException(sprintf(
            'Function target is not callable: "%s"',
            $target
        ));
    }

    $signatureType = getenv('FUNCTION_SIGNATURE_TYPE', true) ?: 'http';

    $invoker = new Invoker($target, $signatureType);
    $response = $invoker->handle();
    (new Emitter())->emit($response);
})();
