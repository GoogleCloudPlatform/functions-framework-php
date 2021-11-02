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

namespace Google\CloudFunctions\Tests;

use BadMethodCallException;
use Google\CloudFunctions\CloudEvent;
use Google\CloudFunctions\CloudEventSdkCompliant;
use PHPUnit\Framework\TestCase;
use DateTimeImmutable;
use DateTimeInterface;

/**
 * @group gcf-framework
 */
class CloudEventSdkCompliantTest extends TestCase
{
    private CloudEvent $cloudevent;

    public function setUp(): void
    {
        $this->cloudevent = new CloudEvent(
            '1413058901901494',
            '//pubsub.googleapis.com/projects/MY-PROJECT/topics/MY-TOPIC',
            '1.0',
            'com.google.cloud.pubsub.topic.publish',
            'application/json',
            'type.googleapis.com/google.logging.v2.LogEntry',
            'My Subject',
            '2020-12-08T20:03:19.162Z',
            [
                "message" => [
                    "data" => "SGVsbG8gdGhlcmU=",
                    "messageId" => "1408577928008405",
                    "publishTime" => "2020-08-06T22:31:14.536Z"
                ],
                "subscription" => "projects/MY-PROJECT/subscriptions/MY-SUB"
            ]
        );
    }

    public function testJsonSerialize(): void
    {
        $wrappedEvent = new CloudEventSdkCompliant($this->cloudevent);

        $want = '{
    "id": "1413058901901494",
    "source": "\/\/pubsub.googleapis.com\/projects\/MY-PROJECT\/topics\/MY-TOPIC",
    "specversion": "1.0",
    "type": "com.google.cloud.pubsub.topic.publish",
    "datacontenttype": "application\/json",
    "dataschema": "type.googleapis.com\/google.logging.v2.LogEntry",
    "subject": "My Subject",
    "time": "2020-12-08T20:03:19.162Z",
    "data": {
        "message": {
            "data": "SGVsbG8gdGhlcmU=",
            "messageId": "1408577928008405",
            "publishTime": "2020-08-06T22:31:14.536Z"
        },
        "subscription": "projects\\/MY-PROJECT\\/subscriptions\\/MY-SUB"
    }
}';

        $this->assertSame($want, json_encode($wrappedEvent, JSON_PRETTY_PRINT));
    }

    public function testWrapsCloudEvent(): void
    {
        $wrappedEvent = new CloudEventSdkCompliant($this->cloudevent);

        $this->assertSame($this->cloudevent->getId(), $wrappedEvent->getId());
        $this->assertSame($this->cloudevent->getSource(), $wrappedEvent->getSource());
        $this->assertSame($this->cloudevent->getType(), $wrappedEvent->getType());
        $this->assertSame($this->cloudevent->getData(), $wrappedEvent->getData());
        $this->assertSame($this->cloudevent->getDataContentType(), $wrappedEvent->getDataContentType());
        $this->assertSame($this->cloudevent->getDataSchema(), $wrappedEvent->getDataSchema());
        $this->assertSame($this->cloudevent->getSubject(), $wrappedEvent->getSubject());
        $this->assertEquals(DateTimeImmutable::createFromFormat(DateTimeInterface::RFC3339_EXTENDED, $this->cloudevent->getTime()), $wrappedEvent->getTime());
    }

    public function testUnimplementedGetExtensionThrowsError(): void
    {
        $wrappedEvent = new CloudEventSdkCompliant($this->cloudevent);
        $this->expectException(BadMethodCallException::class);

        $wrappedEvent->getExtension('attribute');
    }

    public function testUnimplementedGetExtensionsThrowsError(): void
    {
        $wrappedEvent = new CloudEventSdkCompliant($this->cloudevent);
        $this->expectException(BadMethodCallException::class);

        $wrappedEvent->getExtensions();
    }
}
