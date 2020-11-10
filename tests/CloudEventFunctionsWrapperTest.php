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

use Google\CloudFunctions\CloudEventFunctionWrapper;
use Google\CloudFunctions\CloudEvent;
use PHPUnit\Framework\TestCase;
use GuzzleHttp\Psr7\ServerRequest;

/**
 * @group gcf-framework
 */
class CloudEventFunctionWrapperTest extends TestCase
{
    private static $functionCalled;

    public function setUp(): void
    {
        self::$functionCalled = false;
    }

    public function testInvalidCloudEventRequestBody()
    {
        $headers = ['content-type' => 'application/cloudevents+json'];
        $request = new ServerRequest('POST', '/', $headers, 'notjson');
        $cloudEventFunctionWrapper = new CloudEventFunctionWrapper([$this, 'invokeThis']);
        $response = $cloudEventFunctionWrapper->execute($request);
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals(
            'Could not parse CloudEvent: Syntax error',
            (string) $response->getBody()
        );
        $this->assertEquals('crash', $response->getHeaderLine('X-Google-Status'));
    }

    public function testInvalidLegacyEventRequestBody()
    {
        $request = new ServerRequest('POST', '/', [], 'notjson');
        $cloudEventFunctionWrapper = new CloudEventFunctionWrapper([$this, 'invokeThis']);
        $response = $cloudEventFunctionWrapper->execute($request);
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals(
            'Could not parse CloudEvent: Syntax error',
            (string) $response->getBody()
        );
        $this->assertEquals('crash', $response->getHeaderLine('X-Google-Status'));
    }

    public function testNonJsonIsValidInBinaryCloudEventRequestBody()
    {
        $request = new ServerRequest('POST', '/', [
            'ce-id' => 'fooBar',
            'ce-source' => 'my-source',
            'ce-specversion' => '1.0',
            'ce-type' => 'my.type',
        ], 'notjson');
        $cloudEventFunctionWrapper = new CloudEventFunctionWrapper(
            [$this, 'invokeThisPartial']
        );
        $response = $cloudEventFunctionWrapper->execute($request);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testInvalidJsonBinaryCloudEventRequestBody()
    {
        $request = new ServerRequest('POST', '/', [
            'ce-id' => 'fooBar',
            'ce-source' => 'my-source',
            'ce-specversion' => '1.0',
            'ce-type' => 'my.type',
            'Content-Type' => 'application/json',
        ], 'notjson');
        $cloudEventFunctionWrapper = new CloudEventFunctionWrapper(
            [$this, 'invokeThisPartial']
        );
        $response = $cloudEventFunctionWrapper->execute($request);
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals(
            'Could not parse CloudEvent: Syntax error',
            (string) $response->getBody()
        );
        $this->assertEquals('crash', $response->getHeaderLine('X-Google-Status'));
    }

    public function testValidJsonBinaryCloudEventRequestBody()
    {
        $request = new ServerRequest('POST', '/', [
            'ce-id' => 'fooBar',
            'ce-source' => 'my-source',
            'ce-specversion' => '1.0',
            'ce-type' => 'my.type',
            'Content-Type' => 'application/json',
        ], '{"this":"isjson"}');
        $cloudEventFunctionWrapper = new CloudEventFunctionWrapper(
            [$this, 'invokeThisPartial']
        );
        $response = $cloudEventFunctionWrapper->execute($request);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testNoFunctionParameters()
    {
        $this->expectException('LogicException');
        $this->expectExceptionMessage(
            'Your function must have "Google\CloudFunctions\CloudEvent" as the typehint for the first argument'
        );
        $request = new ServerRequest('POST', '/', []);
        $cloudEventFunctionWrapper = new CloudEventFunctionWrapper(
            function () {
            }
        );
    }

    public function testTooManyFunctionParameters()
    {
        $this->expectException('LogicException');
        $this->expectExceptionMessage(
            'If your function accepts more than one parameter the additional parameters must be optional'
        );
        $cloudEventFunctionWrapper = new CloudEventFunctionWrapper(
            function (CloudEvent $foo, $bar) {
            }
        );
    }

    public function testNoTypehintInFunctionParameter()
    {
        $this->expectException('LogicException');
        $this->expectExceptionMessage(
            'Your function must have "Google\CloudFunctions\CloudEvent" as the typehint for the first argument'
        );
        $cloudEventFunctionWrapper = new CloudEventFunctionWrapper(
            function ($foo) {
            }
        );
    }

    public function testWrongTypehintInFunctionParameter()
    {
        $this->expectException('LogicException');
        $this->expectExceptionMessage(
            'Your function must have "Google\CloudFunctions\CloudEvent" as the typehint for the first argument'
        );
        $cloudEventFunctionWrapper = new CloudEventFunctionWrapper(
            function (NotTheRightThing $foo) {
            }
        );
    }

    public function testCorrectTypehintsInFunctionParameter()
    {
        $request = new ServerRequest('POST', '/', []);
        $cloudEventFunctionWrapper = new CloudEventFunctionWrapper(
            function (CloudEvent $foo) {
            }
        );
        $this->assertTrue(true, 'No exception was thrown');
        // Optional parameters are ok
        $cloudEventFunctionWrapper = new CloudEventFunctionWrapper(
            function (CloudEvent $foo = null) {
            }
        );
        $this->assertTrue(true, 'No exception was thrown');
        // additional optional parameters are ok
        $cloudEventFunctionWrapper = new CloudEventFunctionWrapper(
            function (CloudEvent $foo, $bar = null) {
            }
        );
        $this->assertTrue(true, 'No exception was thrown');
    }

    public function testWithFullCloudEvent()
    {
        self::$functionCalled = false;
        $cloudEventFunctionWrapper = new CloudEventFunctionWrapper([$this, 'invokeThis']);
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
        $cloudEventFunctionWrapper->execute($request);
        $this->assertTrue(self::$functionCalled);
    }

    public function testWithNonJSONData()
    {
        self::$functionCalled = false;
        $cloudEventFunctionWrapper = new CloudEventFunctionWrapper([$this, 'invokeThis']);
        $request = new ServerRequest('POST', '/', [
            'ce-id' => '1413058901901494',
            'ce-source' => '//pubsub.googleapis.com/projects/MY-PROJECT/topics/MY-TOPIC',
            'ce-specversion' => '1.0',
            'ce-type' => 'com.google.cloud.pubsub.topic.publish',
            'ce-datacontenttype' => 'application/json',
            'ce-dataschema' => 'type.googleapis.com/google.logging.v2.LogEntry',
            'ce-subject' => 'My Subject',
            'ce-time' => '2020-12-08T20:03:19.162Z',
        ], '123');
        $cloudEventFunctionWrapper->execute($request);
        $this->assertTrue(self::$functionCalled);
    }

    public function invokeThis(CloudEvent $cloudevent)
    {
        $this->assertFalse(self::$functionCalled);
        self::$functionCalled = true;
        $this->assertEquals('1413058901901494', $cloudevent->getId());
        $this->assertEquals('//pubsub.googleapis.com/projects/MY-PROJECT/topics/MY-TOPIC', $cloudevent->getSource());
        $this->assertEquals('1.0', $cloudevent->getSpecVersion());
        $this->assertEquals('com.google.cloud.pubsub.topic.publish', $cloudevent->getType());
        $this->assertEquals('application/json', $cloudevent->getDataContentType());
        $this->assertEquals('type.googleapis.com/google.logging.v2.LogEntry', $cloudevent->getDataSchema());
        $this->assertEquals('My Subject', $cloudevent->getSubject());
        $this->assertEquals('2020-12-08T20:03:19.162Z', $cloudevent->getTime());
    }

    public function testWithNotFullButValidCloudEvent()
    {
        self::$functionCalled = false;
        $cloudEventFunctionWrapper = new CloudEventFunctionWrapper([$this, 'invokeThisPartial']);
        $request = new ServerRequest('POST', '/', [
            'ce-id' => 'fooBar',
            'ce-source' => 'my-source',
            'ce-specversion' => '1.0',
            'ce-type' => 'my.type',
        ], json_encode([
            "key" => "value"
        ]));
        $cloudEventFunctionWrapper->execute($request);
        $this->assertTrue(self::$functionCalled);
    }

    public function invokeThisPartial(CloudEvent $cloudevent)
    {
        $this->assertFalse(self::$functionCalled);
        self::$functionCalled = true;
        $this->assertEquals('fooBar', $cloudevent->getId());
        $this->assertEquals('my-source', $cloudevent->getSource());
        $this->assertEquals('1.0', $cloudevent->getSpecVersion());
        $this->assertEquals('my.type', $cloudevent->getType());
    }

    public function testFromLegacyEventWithContextProperty()
    {
        $cloudEventFunctionsWrapper = new CloudEventFunctionWrapper(
            [$this, 'invokeThisLegacy']
        );
        $request = new ServerRequest('GET', '/', [], json_encode([
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
        ]));
        $cloudEventFunctionsWrapper->execute($request);
        $this->assertTrue(self::$functionCalled);
    }

    public function invokeThisLegacy(CloudEvent $cloudevent)
    {
        $this->assertFalse(self::$functionCalled);
        self::$functionCalled = true;
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

    public function testFromStructuredEventRequest()
    {
        self::$functionCalled = false;
        $cloudEventFunctionWrapper = new CloudEventFunctionWrapper([$this, 'invokeThis']);
        $request = new ServerRequest('POST', '/', [
            'content-type' => 'application/cloudevents+json',
        ], json_encode([
            'id' => '1413058901901494',
            'source' => '//pubsub.googleapis.com/projects/MY-PROJECT/topics/MY-TOPIC',
            'specversion' => '1.0',
            'type' => 'com.google.cloud.pubsub.topic.publish',
            'datacontenttype' => 'application/json',
            'dataschema' => 'type.googleapis.com/google.logging.v2.LogEntry',
            'subject' => 'My Subject',
            'time' => '2020-12-08T20:03:19.162Z',
            'data' => 'foo',
        ]));
        $cloudEventFunctionWrapper->execute($request);
        $this->assertTrue(self::$functionCalled);
    }
}
