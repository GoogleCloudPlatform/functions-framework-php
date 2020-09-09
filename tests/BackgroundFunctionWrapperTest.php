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

use Google\CloudFunctions\BackgroundFunctionWrapper;
use Google\CloudFunctions\Context;
use PHPUnit\Framework\TestCase;
use GuzzleHttp\Psr7\ServerRequest;

/**
 * @group gcf-framework
 */
class BackgroundFunctionWrapperTest extends TestCase
{
    private static $functionCalled = false;

    public function testInvalidRequestBody()
    {
        $this->expectException('RuntimeException');
        $this->expectExceptionMessage('Could not parse request body: Syntax error');
        $request = new ServerRequest('GET', '/', [], 'notjson');
        $backgroundFunctionWrapper = new BackgroundFunctionWrapper([$this, 'invokeThis']);
        $backgroundFunctionWrapper->execute($request);
    }

    public function testEmptyRequestBody()
    {
        $this->expectException('RuntimeException');
        $this->expectExceptionMessage('Could not parse request body: Missing event payload');
        $request = new ServerRequest('GET', '/', [], '');
        $backgroundFunctionWrapper = new BackgroundFunctionWrapper([$this, 'invokeThis']);
        $backgroundFunctionWrapper->execute($request);
    }

    public function testWithContextProperty()
    {
        $backgroundFunctionWrapper = new BackgroundFunctionWrapper([$this, 'invokeThis']);
        $request = new ServerRequest('GET', '/', [], json_encode([
            'data' => 'foo',
            'context' => [
                'eventId' => 'abc',
                'timestamp' => 'def',
                'eventType' => 'ghi',
                'resource' => 'jkl',
            ]
        ]));
        $backgroundFunctionWrapper->execute($request);
        $this->assertTrue(self::$functionCalled);
    }

    public function testWrapperWithoutContextProperty()
    {
        $backgroundFunctionWrapper = new BackgroundFunctionWrapper([$this, 'invokeThis']);
        $request = new ServerRequest('GET', '/', [], json_encode([
            'data' => 'foo',
            'eventId' => 'abc',
            'timestamp' => 'def',
            'eventType' => 'ghi',
            'resource' => 'jkl',
        ]));
        $backgroundFunctionWrapper->execute($request);
        $this->assertTrue(self::$functionCalled);
    }

    public function invokeThis($data, Context $context)
    {
        self::$functionCalled = true;
        $this->assertEquals('foo', $data);
        $this->assertEquals('abc', $context->getEventId());
        $this->assertEquals('def', $context->getTimestamp());
        $this->assertEquals('ghi', $context->getEventType());
        $this->assertEquals('jkl', $context->getResource());
    }
}
