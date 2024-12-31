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

use LogicException;
use ReflectionFunctionAbstract;
use ReflectionParameter;

trait FunctionValidationTrait
{
    abstract public function validateFirstParameter(ReflectionParameter $param): void;

    /**
     * @throws LogicException
     */
    abstract public function throwInvalidFirstParameterException(): void;

    private function validateFunctionSignature(
        ReflectionFunctionAbstract $reflection
    ) {
        $parameters = $reflection->getParameters();
        $parametersCount = count($parameters);

        if ($parametersCount === 0) {
            $this->throwInvalidFirstParameterException();
        }

        $this->validateFirstParameter($parameters[0]);

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
