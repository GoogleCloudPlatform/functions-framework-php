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
use ReflectionFunctionAbstract;

abstract class ValidatingFunctionWrapper extends FunctionWrapper
{
    public function __construct(callable $function)
    {
        parent::__construct($function);

        $this->validateFunctionSignature(
            $this->getFunctionReflection($function)
        );
    }

    abstract protected function getFunctionParameterClassName(): string;

    private function validateFunctionSignature(
        ReflectionFunctionAbstract $reflection
    ) {
        $parameters = $reflection->getParameters();
        $parametersCount = count($parameters);

        // Check there is at least one parameter
        if ($parametersCount === 0) {
            $this->throwInvalidFunctionException();
        }
        // Check the first parameter has the proper typehint
        $type = $parameters[0]->getType();
        $class = $this->getFunctionParameterClassName();
        if (!$type || $type->getName() !== $class) {
            $this->throwInvalidFunctionException();
        }

        if ($parametersCount > 1) {
            for ($i = 1; $i < $parametersCount; $i++) {
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
