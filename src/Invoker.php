<?php

namespace Google\CloudFunctions;

use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Invoker
{
    private $function;

    public function __construct($target, $signatureType)
    {
        if ($signatureType === 'http') {
            $this->function = new HttpFunctionWrapper($target);
        } elseif ($signatureType === 'event') {
            $this->function = new BackgroundFunctionWrapper($target);
        } else {
            throw new InvalidArgumentException(sprintf(
                'Invalid signature type: "%s"', $signatureType));
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
