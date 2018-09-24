<?php

chdir('../../..'); // We're in vendor/google/function-invoker

require_once './vendor/autoload.php';
require_once './index.php';

(function() {
    $entryPoint = getenv('ENTRY_POINT', true);
    if ($entryPoint === false) {
        trigger_error('ENTRY_POINT is not set');
    }
    $triggerType = getenv('FUNCTION_TRIGGER_TYPE', true);
    if ($triggerType === false) {
        trigger_error('FUNCTION_TRIGGER_TYPE is not set');
    }

    $invoker = new Google\CloudFunctions\Invoker($entryPoint, $triggerType);
    $invoker->handle()->send();
})();
