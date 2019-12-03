<?php

use Symfony\Component\HttpFoundation\Request;
use Google\CloudFunctions\Context;

function helloHttp(Request $request) {
    return sprintf("Hello %s from PHP HTTP function!" . PHP_EOL,
        $request->query->get('name') ?: 'World'
    );
}

function helloEvent($data, Context $context) {
    printf("Hello %s PHP Event function!" . PHP_EOL, $data);
}
