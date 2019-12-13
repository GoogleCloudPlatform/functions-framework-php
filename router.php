<?php

/**
 * Determine the autoload file to load.
 */
if (file_exists(__DIR__ . '/../../autoload.php')) {
    // when running from vendor/google/cloud-error-reporting
    require_once __DIR__ . '/../../vendor/autoload.php';
} elseif (file_exists(__DIR__ . '/vendor/autoload.php')) {
    // when running from git clone.
    require_once __DIR__ . '/vendor/autoload.php';
}

/**
 * Determine the function source file to load
 */
if ($functionSource = getenv('FUNCTION_SOURCE', true)) {
    // when function src is set by environment variable
    if (!file_exists($functionSource)) {
        throw new InvalidArgumentException(sprintf(
            'Unable to load function from "%s"', $functionSource));
    }
    require_once $functionSource;
} elseif (file_exists($functionSource = __DIR__ . '/../../../index.php')) {
    // when running from vendor/google/cloud-functions-framework, default to
    // loading functions from "index.php" in the root of the project.
    require_once $functionSource;
}

/**
 * Invoke the function based on the function type.
 */
(function() {
    $target = getenv('FUNCTION_TARGET', true);
    if ($target === false) {
        trigger_error('FUNCTION_TARGET is not set');
    }
    $signatureType = getenv('FUNCTION_SIGNATURE_TYPE', true);
    if ($signatureType === false) {
        trigger_error('FUNCTION_SIGNATURE_TYPE is not set');
    }

    $invoker = new Google\CloudFunctions\Invoker($target, $signatureType);
    $invoker->handle()->send();
})();
