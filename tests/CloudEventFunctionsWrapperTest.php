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

namespace Google\CloudFunctions\Tests;

use Google\CloudFunctions\CloudEventFunctionWrapper;
use Google\CloudFunctions\CloudEvent;
use PHPUnit\Framework\TestCase;
use GuzzleHttp\Psr7\ServerRequest;

/**
 * @group gcf-framework
 */
class CloudEventFunctionWrapperTest extends TestCase
{
    private static $functionCalled = false;

    public function testInvalidRequestBody()
    {
        $this->expectException('RuntimeException');
        $this->expectExceptionMessage('Could not parse CloudEvent: Syntax error');
        $request = new ServerRequest('POST', '/', [], 'notjson');
        $cloudEventFunctionWrapper = new CloudEventFunctionWrapper([$this, 'invokeThis']);
        $cloudEventFunctionWrapper->execute($request);
    }

    public function testWithCloudEvent()
    {
        $CloudEventFunctionWrapper = new CloudEventFunctionWrapper([$this, 'invokeThis']);
        $request = new ServerRequest('POST', '/', [
            'ce-id' => '1413058901901494',
            'ce-source' => '//pubsub.googleapis.com/projects/MY-PROJECT/topics/MY-TOPIC',
            'ce-specversion' => '1.0',
            'ce-type' => 'com.google.cloud.pubsub.topic.publish',
            'ce-datacontenttype' => 'application/json',
            'ce-dataschema' => 'type.googleapis.com/google.logging.v2.LogEntry',
            'ce-subject' => 'My Subject',
            'ce-time' => '2020-12-08T20:03:19.162Z',
        ], json_encode([
            "message" => [
                "data" => "SGVsbG8gdGhlcmU=",
                "messageId" => "1408577928008405",
                "publishTime" => "2020-08-06T22:31:14.536Z"
            ],
            "subscription" => "projects/MY-PROJECT/subscriptions/MY-SUB"
        ]));
        $CloudEventFunctionWrapper->execute($request);
        $this->assertTrue(self::$functionCalled);
    }

    public function invokeThis(CloudEvent $cloudevent)
    {
        self::$functionCalled = true;
        $this->assertEquals('1413058901901494', $cloudevent->id);
        $this->assertEquals('//pubsub.googleapis.com/projects/MY-PROJECT/topics/MY-TOPIC', $cloudevent->source);
        $this->assertEquals('1.0', $cloudevent->specversion);
        $this->assertEquals('com.google.cloud.pubsub.topic.publish', $cloudevent->type);
        $this->assertEquals('application/json', $cloudevent->datacontenttype);
        $this->assertEquals('type.googleapis.com/google.logging.v2.LogEntry', $cloudevent->dataschema);
        $this->assertEquals('My Subject', $cloudevent->subject);
        $this->assertEquals('2020-12-08T20:03:19.162Z', $cloudevent->time);
    }
}
