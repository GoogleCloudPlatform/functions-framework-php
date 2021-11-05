<?php

/**
 * Copyright 2020 Google LLC.
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

use BadMethodCallException;
use JsonSerializable;
use CloudEvents\V1\CloudEventInterface;
use DateTimeImmutable;
use DateTimeInterface;

/**
 * @internal
 * Wraps a Google\CloudFunctions\CloudEvent to comply with
 * CloudEvents\V1\CloudEventInterface.
 */
class CloudEventSdkCompliant implements JsonSerializable, CloudEventInterface
{
    private $cloudevent;

    public function __construct(
        CloudEvent $cloudevent
    ) {
        $this->cloudevent = $cloudevent;
    }

    public function getId(): string
    {
        return $this->cloudevent->getId();
    }
    public function getSource(): string
    {
        return $this->cloudevent->getSource();
    }
    public function getSpecVersion(): string
    {
        return $this->cloudevent->getSpecVersion();
    }
    public function getType(): string
    {
        return $this->cloudevent->getType();
    }
    public function getDataContentType(): ?string
    {
        return $this->cloudevent->getDataContentType();
    }
    public function getDataSchema(): ?string
    {
        return $this->cloudevent->getDataSchema();
    }
    public function getSubject(): ?string
    {
        return $this->cloudevent->getSubject();
    }
    public function getTime(): ?DateTimeImmutable
    {
        return DateTimeImmutable::createFromFormat(DateTimeInterface::RFC3339_EXTENDED, $this->cloudevent->getTime());
    }
    public function getExtension(string $attribute)
    {
        throw new BadMethodCallException('getExtension() is not currently supported by Functions Framework PHP');
    }
    public function getExtensions(): array
    {
        throw new BadMethodCallException('getExtensions() is not currently supported by Functions Framework PHP');
    }
    /**
     * @return mixed
     */
    public function getData()
    {
        return $this->cloudevent->getData();
    }

    public function jsonSerialize()
    {
        return $this->cloudevent->jsonSerialize();
    }

    public function __toString()
    {
        return $this->cloudevent->__toString();
    }
}
