<?php


// We're in vendor/google/cloud-functions-framework

require_once __DIR__ . '/../../autoload.php';
require_once getenv('FUNCTION_SOURCE', true) ?: __DIR__ . '/../../../index.php';

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
