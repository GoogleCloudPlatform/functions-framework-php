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

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Closure;
use LogicException;
use ReflectionFunction;
use ReflectionFunctionAbstract;
use ReflectionMethod;

abstract class FunctionWrapper
{
    const FUNCTION_STATUS_HEADER = 'X-Google-Status';

    protected $function;

    public function __construct(callable $function, array $signature = null)
    {
        $this->validateFunctionSignature(
            $this->getFunctionReflection($function)
        );

        $this->function = $function;
    }

    abstract public function execute(
        ServerRequestInterface $request
    ): ResponseInterface;

    abstract protected function getFunctionParameterClassName(): string;

    private function getFunctionReflection(
        callable $function
    ): ReflectionFunctionAbstract {
        if ($function instanceof Closure) {
            return new ReflectionFunction($function);
        }
        if (is_string($function)) {
            $parts = explode('::', $function);
            return count($parts) > 1
                ? new ReflectionMethod($parts[0], $parts[1])
                : new ReflectionFunction($function);
        }
        if (is_array($function)) {
            return new ReflectionMethod($function[0], $function[1]);
        }

        return new ReflectionMethod($function, '__invoke');
    }

    private function validateFunctionSignature(
        ReflectionFunctionAbstract $reflection
    ) {
        $parameters = $reflection->getParameters();
        // Check there is at least one parameter
        if (count($parameters) < 1) {
            $this->throwInvalidFunctionException();
        }
        // Check the first parameter has the proper typehint
        $type = $parameters[0]->getType();
        $class = $this->getFunctionParameterClassName();
        if (!$type || $type->getName() !== $class) {
            $this->throwInvalidFunctionException();
        }

        if (count($parameters) > 1) {
            for ($i = 1; $i < count($parameters); $i++) {
                if (!$parameters[$i]->isOptional()) {
                    throw new LogicException(
                        'If your function accepts more than one parameter the '
                        . 'additional parameters must be optional'
                    );
                }
            }
        }
    }

    private function throwInvalidFunctionException()
    {
        throw new LogicException(sprintf(
            'Your function must have "%s" as the typehint for the first argument',
            $this->getFunctionParameterClassName()
        ));
    }
}
