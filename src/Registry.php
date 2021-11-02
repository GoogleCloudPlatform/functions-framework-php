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

class Registry
{
    public const TYPE_HTTP = "http";
    public const TYPE_CLOUDEVENT = "cloudevent";

    private static array $fnRegistry = [];

    private function __construct()
    {
    }

    public static function registerHttp(string $name, callable $fn)
    {
        Registry::$fnRegistry[$name] = [self::TYPE_HTTP, $fn];
    }

    public static function registerCloudEvent(string $name, callable $fn)
    {
        Registry::$fnRegistry[$name] = [self::TYPE_CLOUDEVENT, $fn];
    }

    public static function contains(string $name)
    {
        return array_key_exists($name, Registry::$fnRegistry);
    }

    public static function getFunctionType(string $name)
    {
        return Registry::$fnRegistry[$name][0];
    }

    public static function getFunctionHandle(string $name)
    {
        return Registry::$fnRegistry[$name][1];
    }
}
