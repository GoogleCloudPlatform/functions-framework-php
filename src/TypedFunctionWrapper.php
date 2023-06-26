<?php

/**
 * Copyright 2023 Google LLC.
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

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Closure;
use Exception;
use GuzzleHttp\Psr7\Response;
use LogicException;
use ReflectionClass;
use ReflectionException;
use ReflectionFunctionAbstract;
use ReflectionParameter;

class TypedFunctionWrapper extends FunctionWrapper
{
    use FunctionValidationTrait;

    public const FUNCTION_STATUS_HEADER = 'X-Google-Status';

    /** @var callable */
    protected $function;
    /** @var ReflectionClass */
    protected $functionArgClass;

    public function __construct(callable $function)
    {
        parent::__construct($function);

        $this->validateFunctionSignature(
            $this->getFunctionReflection($function)
        );
    }

    private function validateFirstParameter(ReflectionParameter $param): void
    {
        $type = $param->getType();
        if ($type == null) {
            $this->throwInvalidFirstParameterException();
        }

        try {
            $this->functionArgClass = new ReflectionClass($type->getName());
        } catch (ReflectionException $e) {
            $name = $type->getName();
            $message = $e->getMessage();
            throw new LogicException("Could not find function parameter type $name, error: $message");
        }
    }

    private function throwInvalidFirstParameterException(): void
    {
        throw new LogicException(
            'Your function must declare exactly one required parameter that has a valid type hint'
        );
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws BadRequestException
     */
    public function execute(ServerRequestInterface $request): ResponseInterface
    {
        try {
            $argInst = $this->functionArgClass->newInstance();
        } catch (ReflectionException $e) {
            throw new LogicException('Function request class cannot be instantiated');
        }
        $body = $request->getBody()->getContents();

        try {
            $argInst->mergeFromJsonString($body);
        } catch (Exception $e) {
            throw new BadRequestError($e->getMessage(), 0, $e);
        }

        $funcResult = call_user_func($this->function, $argInst);

        if (!method_exists($funcResult, "serializeToJsonString")) {
            throw new LogicException("Return type must implement 'serializeToJsonString'");
        }
        $resultJson = $funcResult->serializeToJsonString();

        return new Response(200, ['content-type' => 'application/json'], $resultJson);
    }
}
