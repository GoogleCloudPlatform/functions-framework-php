<?php

namespace Google\CloudFunctions;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class BackgroundFunctionWrapper extends FunctionWrapper
{
    public function __construct(callable $function)
    {
        parent::__construct($function);
    }

    public function execute(Request $request): Response {
        $event = json_decode($request->getContent(), true);
        if (json_last_error() != JSON_ERROR_NONE) {
            throw new \RuntimeException('Could not parse request body: ' . json_last_error_msg());
        }

        $data = $event['data'];
        $context = Context::fromArray($event['context']);
        call_user_func($this->function, $data, $context);

        return new Response();
    }
}
