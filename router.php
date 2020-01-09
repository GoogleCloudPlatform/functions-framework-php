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

/**
 * Determine the autoload file to load.
 */
if (file_exists(__DIR__ . '/../../autoload.php')) {
    // when running from vendor/google/cloud-functions-framework
    require_once __DIR__ . '/../../autoload.php';
} elseif (file_exists(__DIR__ . '/vendor/autoload.php')) {
    // when running from git clone.
    require_once __DIR__ . '/vendor/autoload.php';
}

/**
 * Determine the function source file to load
 */
// Ensure function source is loaded relative to the application root directory
$documentRoot = __DIR__ . '/../../../';
if ($functionSource = getenv('FUNCTION_SOURCE', true)) {
    if (0 !== strpos($functionSource, '/')) {
        // Make the path relative
        $relativeSource = $documentRoot . $functionSource;
        if (!file_exists($relativeSource)) {
            throw new RuntimeException(sprintf(
                'Unable to load function from "%s"',
                getenv('FUNCTION_SOURCE', true)
            ));
        }
        require_once $relativeSource;
    } else {
        require_once $functionSource;
    }
} elseif (file_exists($defaultSource = $documentRoot . 'index.php')) {
    // When running from vendor/google/cloud-functions-framework, default to
    // "index.php" in the root of the application.
    require_once $defaultSource;
} else {
    // Do nothing - assume the function source is being autoloaded.
}

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
            'Function target is not callable: "%s"', $target));
    }
    $signatureType = getenv('FUNCTION_SIGNATURE_TYPE', true);
    if (false === $signatureType) {
        throw new RuntimeException('FUNCTION_SIGNATURE_TYPE is not set');
    }

    $invoker = new Google\CloudFunctions\Invoker($target, $signatureType);
    $invoker->handle()->send();
})();
