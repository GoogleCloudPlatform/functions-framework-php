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

namespace Google\CloudFunctions\Tests;

use Google\CloudFunctions\LegacyEventMapper;
use PHPUnit\Framework\TestCase;

/**
 * @group gcf-framework
 */
class LegacyEventMapperTest extends TestCase
{
    public function testWithContextProperty()
    {
        $mapper = new LegacyEventMapper();
        $jsonData = [
            'data' => 'foo',
            'context' => [
                'eventId' => '1413058901901494',
                'timestamp' => '2020-12-08T20:03:19.162Z',
                'eventType' => 'providers/cloud.pubsub/eventTypes/topic.publish',
                'resource' => [
                    'name' => 'projects/MY-PROJECT/topics/MY-TOPIC',
                    'service' => 'pubsub.googleapis.com'
                ],
            ]
        ];
        $cloudevent = $mapper->fromJsonData($jsonData);

        $this->assertEquals('1413058901901494', $cloudevent->getId());
        $this->assertEquals(
            '//pubsub.googleapis.com/projects/MY-PROJECT/topics/MY-TOPIC',
            $cloudevent->getSource()
        );
        $this->assertEquals('1.0', $cloudevent->getSpecVersion());
        $this->assertEquals(
            'google.cloud.pubsub.topic.v1.messagePublished',
            $cloudevent->getType()
        );
        $this->assertEquals('application/json', $cloudevent->getDataContentType());
        $this->assertEquals(null, $cloudevent->getDataSchema());
        $this->assertEquals(null, $cloudevent->getSubject());
    }

    public function testWithoutContextProperty()
    {
        $mapper = new LegacyEventMapper();
        $jsonData = [
            'data' => 'foo',
            'eventId' => '1413058901901494',
            'timestamp' => '2020-12-08T20:03:19.162Z',
            'eventType' => 'providers/cloud.pubsub/eventTypes/topic.publish',
            'resource' => [
                'name' => 'projects/MY-PROJECT/topics/MY-TOPIC',
                'service' => 'pubsub.googleapis.com'
            ],
        ];
        $cloudevent = $mapper->fromJsonData($jsonData);

        $this->assertEquals('1413058901901494', $cloudevent->getId());
        $this->assertEquals(
            '//pubsub.googleapis.com/projects/MY-PROJECT/topics/MY-TOPIC',
            $cloudevent->getSource()
        );
        $this->assertEquals('1.0', $cloudevent->getSpecVersion());
        $this->assertEquals(
            'google.cloud.pubsub.topic.v1.messagePublished',
            $cloudevent->getType()
        );
        $this->assertEquals('application/json', $cloudevent->getDataContentType());
        $this->assertEquals(null, $cloudevent->getDataSchema());
        $this->assertEquals(null, $cloudevent->getSubject());
        $this->assertEquals('2020-12-08T20:03:19.162Z', $cloudevent->getTime());
    }

    public function testResourceAsString()
    {
        $mapper = new LegacyEventMapper();
        $jsonData = [
            'data' => 'foo',
            'eventId' => '1413058901901494',
            'timestamp' => '2020-12-08T20:03:19.162Z',
            'eventType' => 'providers/cloud.pubsub/eventTypes/topic.publish',
            'resource' => 'projects/MY-PROJECT/topics/MY-TOPIC',
        ];
        $cloudevent = $mapper->fromJsonData($jsonData);

        $this->assertEquals('1413058901901494', $cloudevent->getId());
        $this->assertEquals(
            '//pubsub.googleapis.com/projects/MY-PROJECT/topics/MY-TOPIC',
            $cloudevent->getSource()
        );
        $this->assertEquals('1.0', $cloudevent->getSpecVersion());
        $this->assertEquals(
            'google.cloud.pubsub.topic.v1.messagePublished',
            $cloudevent->getType()
        );
        $this->assertEquals('application/json', $cloudevent->getDataContentType());
        $this->assertEquals(null, $cloudevent->getDataSchema());
        $this->assertEquals(null, $cloudevent->getSubject());
        $this->assertEquals('2020-12-08T20:03:19.162Z', $cloudevent->getTime());
    }

    public function testCloudStorage()
    {
        $mapper = new LegacyEventMapper();
        $jsonData = [
            'data' => 'foo',
            'context' => [
                'eventId' => '1413058901901494',
                'timestamp' => '2020-12-08T20:03:19.162Z',
                'eventType' => 'google.storage.object.finalize',
                'resource' => [
                    'name' => 'projects/_/buckets/sample-bucket/objects/MyFile#1588778055917163',
                    'service' => 'storage.googleapis.com'
                ],
            ]
        ];
        $cloudevent = $mapper->fromJsonData($jsonData);

        $this->assertEquals('1413058901901494', $cloudevent->getId());
        $this->assertEquals(
            '//storage.googleapis.com/projects/_/buckets/sample-bucket',
            $cloudevent->getSource()
        );
        $this->assertEquals('1.0', $cloudevent->getSpecVersion());
        $this->assertEquals(
            'google.cloud.storage.object.v1.finalized',
            $cloudevent->getType()
        );
        $this->assertEquals('application/json', $cloudevent->getDataContentType());
        $this->assertEquals(null, $cloudevent->getDataSchema());
        $this->assertEquals(
            'objects/MyFile#1588778055917163',
            $cloudevent->getSubject()
        );
        $this->assertEquals('2020-12-08T20:03:19.162Z', $cloudevent->getTime());
    }
}
