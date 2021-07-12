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

use Google\CloudFunctions\HttpFunctionWrapper;
use GuzzleHttp\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @group gcf-framework
 */
class HttpFunctionWrapperTest extends TestCase
{
    public function testNoFunctionParameters(): void
    {
        $this->expectException('LogicException');
        $this->expectExceptionMessage(
            'Your function must have "Psr\Http\Message\ServerRequestInterface" as the typehint for the first argument'
        );
        $request = new ServerRequest('POST', '/', []);
        $httpFunctionWrapper = new HttpFunctionWrapper(
            function () {
            }
        );
    }

    public function testTooManyFunctionParameters(): void
    {
        $this->expectException('LogicException');
        $this->expectExceptionMessage(
            'If your function accepts more than one parameter the additional parameters must be optional'
        );
        $httpFunctionWrapper = new HttpFunctionWrapper(
            function (ServerRequestInterface $foo, $bar) {
            }
        );
    }

    public function testNoTypehintInFunctionParameter(): void
    {
        $this->expectException('LogicException');
        $this->expectExceptionMessage(
            'Your function must have "Psr\Http\Message\ServerRequestInterface" as the typehint for the first argument'
        );
        $httpFunctionWrapper = new HttpFunctionWrapper(
            function ($foo) {
            }
        );
    }

    public function testWrongTypehintInFunctionParameter(): void
    {
        $this->expectException('LogicException');
        $this->expectExceptionMessage(
            'Your function must have "Psr\Http\Message\ServerRequestInterface" as the typehint for the first argument'
        );
        $httpFunctionWrapper = new HttpFunctionWrapper(
            function (NotTheRightThing $foo) {
            }
        );
    }

    public function testCorrectTypehintsInFunctionParameter(): void
    {
        $request = new ServerRequest('POST', '/', []);
        $httpFunctionWrapper = new HttpFunctionWrapper(
            function (ServerRequestInterface $foo) {
            }
        );
        $this->assertTrue(true, 'No exception was thrown');
        // Optional parameters are ok
        $httpFunctionWrapper = new HttpFunctionWrapper(
            function (ServerRequestInterface $foo = null) {
            }
        );
        $this->assertTrue(true, 'No exception was thrown');
        // additional optional parameters are ok
        $httpFunctionWrapper = new HttpFunctionWrapper(
            function (ServerRequestInterface $foo, $bar = null) {
            }
        );
        $this->assertTrue(true, 'No exception was thrown');
    }

    public function testHttpHttpFunctionWrapper(): void
    {
        $httpFunctionWrapper = new HttpFunctionWrapper([$this, 'invokeThis']);
        $request = new ServerRequest('GET', '/');
        $response = $httpFunctionWrapper->execute($request);
        $this->assertSame('Invoked!', (string) $response->getBody());
    }

    public function testHttpErrorPaths(): void
    {
        $httpFunctionWrapper = new HttpFunctionWrapper([$this, 'invokeThis']);
        $request = new ServerRequest('GET', '/robots.txt');
        $response = $httpFunctionWrapper->execute($request);
        $this->assertSame(404, $response->getStatusCode());
        $this->assertEmpty((string) $response->getBody());
        $request = new ServerRequest('GET', '/favicon.ico');
        $response = $httpFunctionWrapper->execute($request);
        $this->assertSame(404, $response->getStatusCode());
        $this->assertEmpty((string) $response->getBody());
    }

    public function invokeThis(ServerRequestInterface $request): string
    {
        return 'Invoked!';
    }
}
