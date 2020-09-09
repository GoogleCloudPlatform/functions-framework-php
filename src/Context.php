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
    private $eventId;
    private $timestamp;
    private $eventType;
    private $resource;

    public function __construct(
        ?string $eventId,
        ?string $timestamp,
        ?string $eventType,
        ?string $resource
    ) {
        $this->eventId = $eventId;
        $this->timestamp = $timestamp;
        $this->eventType = $eventType;
        $this->resource = $resource;
    }

    public function getEventId(): ?string
    {
        return $this->eventId;
    }

    public function getEventType(): ?string
    {
        return $this->eventType;
    }

    public function getTimestamp(): ?string
    {
        return $this->timestamp;
    }

    public function getResource(): ?string
    {
        return $this->resource;
    }

    public static function fromArray(array $arr)
    {
        $args = [];
        $argKeys = ['eventId', 'timestamp', 'eventType', 'resource'];
        foreach ($argKeys as $key) {
            $args[] = $arr[$key] ?? null;
        }

        return new static(...$args);
    }
}
