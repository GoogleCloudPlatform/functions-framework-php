<?php

/**
 * Copyright 2021 Google LLC.
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

class FunctionsFramework
{
    private function __construct()
    {
        // Constructor disabled because this class should only be used statically.
    }

    public static function http(string $name, callable $fn)
    {
        Invoker::registerFunction($name, new HttpFunctionWrapper($fn));
    }

    public static function cloudEvent(string $name, callable $fn)
    {
        Invoker::registerFunction($name, new CloudEventFunctionWrapper($fn, true));
    }
}
