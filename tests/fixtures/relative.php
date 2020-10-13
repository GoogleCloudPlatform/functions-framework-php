<?php

use Psr\Http\Message\ServerRequestInterface;

function helloDefault(ServerRequestInterface $request)
{
    return "Hello Relative!";
}
