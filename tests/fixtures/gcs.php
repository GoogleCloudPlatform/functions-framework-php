<?php

use Psr\Http\Message\ServerRequestInterface;

function helloDefault(ServerRequestInterface $request)
{
    $wrappers = stream_get_wrappers();

    return sprintf(
        'GCS Stream Wrapper is %s',
        in_array('gs', $wrappers) ? 'registered' : 'not registered'
    );
}
