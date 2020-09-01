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

class Context
{
    public $eventId;
    public $timestamp;
    public $eventType;
    public $resource;

    public function __construct($eventId, $timestamp, $eventType, $resource)
    {
        $this->eventId = $eventId;
        $this->timestamp = $timestamp;
        $this->eventType = $eventType;
        $this->resource = $resource;
    }

    public static function fromArray(array $arr)
    {
        $argKeys = ['eventId', 'timestamp', 'eventType', 'resource'];
        $args = [];
        foreach ($argKeys as $key) {
            $args[] = $arr[$key] ?? null;
        }

        return new static(...$args);
    }
}
