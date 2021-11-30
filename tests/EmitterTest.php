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

use Google\CloudFunctions\Emitter;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

/**
 * @group gcf-framework
 * @runClassInSeparateProcess
 */
class EmitterTest extends TestCase
{
    public function testEmit(): void
    {
        if (!extension_loaded('xdebug')) {
            $this->markTestSkipped('xdebug extension required');
        }
        $response = new Response(200, ['foo-header' => 'bar'], 'Foo');
        $emitter = new Emitter();
        ob_start();
        $emitter->emit($response);
        $headers = xdebug_get_headers();
        $output = ob_get_clean();

        $this->assertSame('Foo', $output);
        $this->assertContains('Foo-Header:bar', $headers);
        $this->assertSame(200, http_response_code());
    }

    public function testSingleHeader(): void
    {
        $emitter = new TestEmitter();
        $emitter->emit(new Response(200, ['foo-header' => 'bar']));

        $this->assertSame('Foo-Header:bar', $emitter->headers[1][0]);
        $this->assertTrue($emitter->headers[1][1]);
        $this->assertSame(200, $emitter->headers[1][2]);
    }

    public function testRepeatHeaders(): void
    {
        $emitter = new TestEmitter();
        $emitter->emit(new Response(200, ['foo-header' => ['bar', 'baz']]));

        $this->assertSame('Foo-Header:bar', $emitter->headers[1][0]);
        $this->assertTrue($emitter->headers[1][1]);
        $this->assertSame(200, $emitter->headers[1][2]);

        $this->assertSame('Foo-Header:baz', $emitter->headers[2][0]);
        $this->assertFalse($emitter->headers[2][1]);
        $this->assertSame(200, $emitter->headers[2][2]);
    }

    public function testCookies(): void
    {
        $emitter = new TestEmitter();
        $emitter->emit(new Response(200, ['Set-Cookie' => ['1', '2']]));

        $this->assertSame('Set-Cookie:1', $emitter->headers[1][0]);
        $this->assertFalse($emitter->headers[1][1]);
        $this->assertSame(200, $emitter->headers[1][2]);

        $this->assertSame('Set-Cookie:2', $emitter->headers[2][0]);
        $this->assertFalse($emitter->headers[2][1]);
        $this->assertSame(200, $emitter->headers[2][2]);
    }

    public function testStatusLine(): void
    {
        $emitter = new TestEmitter();
        $emitter->emit(new Response(200));

        $this->assertSame('HTTP/1.1 200 OK', $emitter->headers[0][0]);
        $this->assertTrue($emitter->headers[0][1]);
        $this->assertSame(200, $emitter->headers[0][2]);
    }

    public function testStatusLineEmptyReasonPhrase(): void
    {
        $emitter = new TestEmitter();
        $emitter->emit(new Response(419));

        $this->assertSame('HTTP/1.1 419', $emitter->headers[0][0]);
        $this->assertTrue($emitter->headers[0][1]);
        $this->assertSame(419, $emitter->headers[0][2]);
    }
}

class TestEmitter extends Emitter
{
    public $headers;

    protected function header(
        string $headerLine,
        bool $replace,
        int $statusCode
    ): void {
        $this->headers[] = [$headerLine, $replace, $statusCode];
    }
}
