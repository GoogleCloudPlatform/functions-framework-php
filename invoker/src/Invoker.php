<?php

namespace Google\CloudFunctions;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Invoker
{
    private $function;

    public function __construct($entryPoint, $triggerType)
    {
        if ($triggerType === 'HTTP_TRIGGER') {
            $this->function = new HttpFunctionWrapper($entryPoint);
        } else {
            $this->function = new BackgroundFunctionWrapper($entryPoint);
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
