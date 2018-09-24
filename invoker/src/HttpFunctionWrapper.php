<?php

namespace Google\CloudFunctions;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class HttpFunctionWrapper extends FunctionWrapper
{
    public function __construct(callable $function)
    {
        parent::__construct($function);
    }

    public function execute(Request $request): Response {
        $response = call_user_func($this->function, $request);

        if (is_string($response)) {
            $response = new Response($response);
        }

        return $response;
    }
}
