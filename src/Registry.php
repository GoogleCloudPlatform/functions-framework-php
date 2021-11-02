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

use Exception;

/**
 * @internal
 * Singleton class to track registered functions.
 */
class Registry
{
    public const TYPE_HTTP = "http";
    public const TYPE_CLOUDEVENT = "cloudevent";

    private array $fnRegistry = [];
    private static $instance = null;

    /**
     * Get the singleton Registry instance.
     */
    public static function getInstance()
    {
        if (self::$instance == null) {
            self::$instance = new Registry();
        }

        return self::$instance;
    }

    /**
     * is not allowed to call from outside to prevent from creating multiple instances,
     * to use the singleton, you have to obtain the instance from Singleton::getInstance() instead
     */
    private function __construct()
    {
    }

    /**
     * prevent the instance from being cloned (which would create a second instance of it)
     */
    private function __clone()
    {
    }

    /**
     * prevent from being unserialized (which would create a second instance of it)
     */
    public function __wakeup()
    {
        throw new Exception("Cannot unserialize singleton");
    }

    public function registerHttp(string $name, callable $fn)
    {
        $this->fnRegistry[$name] = [self::TYPE_HTTP, $fn];
    }

    public function registerCloudEvent(string $name, callable $fn)
    {
        $this->fnRegistry[$name] = [self::TYPE_CLOUDEVENT, $fn];
    }

    public function contains(string $name)
    {
        return array_key_exists($name, $this->fnRegistry);
    }

    public function getFunctionType(string $name)
    {
        return $this->fnRegistry[$name][0];
    }

    public function getFunctionHandle(string $name)
    {
        return $this->fnRegistry[$name][1];
    }
}
