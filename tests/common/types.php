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

namespace Google\CloudFunctions\Tests\Common;

use LogicException;

class BadType
{
}

class IntValue
{
    /** @var int */
    public $value;

    public function __construct(int $value = 0)
    {
        $this->value = $value;
    }

    public function serializeToJsonString(): string
    {
        return "$this->value";
    }

    public function mergeFromJsonString(string $body): void
    {
        $this->value = intval($body);
    }
}

class NotParseable
{
    public function mergeFromJsonString(string $body): void
    {
        throw new LogicException("could not parse");
    }
}
