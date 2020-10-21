<?php

use Psr\Http\Message\ServerRequestInterface;
use GuzzleHttp\Psr7\Response;

function testStatusCode(ServerRequestInterface $request)
{
    return new Response(418);
}
