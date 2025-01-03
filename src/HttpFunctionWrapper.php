<?php

/**
 * Copyright 2019 Google LLC.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *      http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Google\CloudFunctions;

use LogicException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use GuzzleHttp\Psr7\Response;
use ReflectionParameter;

class HttpFunctionWrapper extends FunctionWrapper
{
    use FunctionValidationTrait;

    public function __construct(callable $function)
    {
        parent::__construct($function);

        $this->validateFunctionSignature(
            $this->getFunctionReflection($function)
        );
    }

    private function throwInvalidFirstParameterException(): void
    {
        throw new LogicException(sprintf(
            'Your function must have "%s" as the typehint for the first argument',
            ServerRequestInterface::class
        ));
    }

    private function validateFirstParameter(ReflectionParameter $param): void
    {
        $type = $param->getType();
        if (!$type || $type->getName() !== ServerRequestInterface::class) {
            $this->throwInvalidFirstParameterException();
        }
    }

    public function execute(ServerRequestInterface $request): ResponseInterface
    {
        $path = $request->getUri()->getPath();
        if ($path == '/robots.txt' || $path == '/favicon.ico') {
            return new Response(404);
        }

        $response = call_user_func($this->function, $request);

        if (is_string($response)) {
            return new Response(200, [], $response);
        } elseif ($response instanceof ResponseInterface) {
            return $response;
        }

        throw new LogicException(
            'Function response must be string or ' . ResponseInterface::class
        );
    }
}
