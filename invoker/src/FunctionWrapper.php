<?php

namespace Google\CloudFunctions;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

abstract class FunctionWrapper
{
    protected $function;

    public function __construct(callable $function, array $signature = null)
    {
        $this->function = $function;

        // TODO: validate function signature, if present.
    }

    abstract public function execute(Request $request): Response;
}
