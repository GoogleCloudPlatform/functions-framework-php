<?php

chdir('../../..'); // We're in vendor/google/function-invoker

require_once './vendor/autoload.php';
require_once './index.php';

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
