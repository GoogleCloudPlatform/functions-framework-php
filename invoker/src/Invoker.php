<?php

namespace Google\CloudFunctions;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Invoker
{
    private $function;

    public function __construct($target, $signatureType)
    {
        if ($signatureType === 'http') {
            $this->function = new HttpFunctionWrapper($target);
        } else {
            $this->function = new BackgroundFunctionWrapper($target);
        }
    }

    public function handle(Request $request = null) : Response
    {
        if ($request === null) {
            $request = Request::createFromGlobals();
        }

        return $this->function->execute($request);
    }
}
