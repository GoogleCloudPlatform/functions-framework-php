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

use CloudEvents\V1\CloudEventInterface;
use Exception;
use Google\CloudFunctions\CloudEvent;
use Google\CloudFunctions\FunctionsFramework;
use Google\CloudFunctions\Invoker;
use Google\CloudFunctions\FunctionWrapper;
use Google\CloudFunctions\Tests\Common\IntValue;
use Google\CloudFunctions\Tests\Common\NotParseable;
use GuzzleHttp\Psr7\ServerRequest;
use LogicException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use ReflectionClass;

/**
 * @group gcf-framework
 */
class InvokerTest extends TestCase
{
    private static string $cloudeventResponse;

    public function testInvalidSignatureType(): void
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('Invalid signature type: "invalid-signature-type"');
        new Invoker([$this, 'invokeThis'], 'invalid-signature-type');
    }

    public function testHttpInvoker(): void
    {
        $invoker = new Invoker([$this, 'invokeThis'], 'http');
        $response = $invoker->handle();
        $this->assertSame('Invoked!', (string) $response->getBody());
    }

    public function testHttpInvokerDeclarative(): void
    {
        FunctionsFramework::http('helloHttp', function (ServerRequestInterface $request) {
            return "Hello HTTP!";
        });

        // signature type ignored due to declarative signature
        $invoker = new Invoker('helloHttp', 'cloudevent');
        $response = $invoker->handle();
        $this->assertSame('Hello HTTP!', (string) $response->getBody());
    }

    public function testTypedInvokerDeclarative(): void
    {
        FunctionsFramework::typed('helloTyped', function (IntValue $request) {
            return new IntValue($request->value + 1);
        });

        // signature type ignored due to declarative signature
        $invoker = new Invoker('helloTyped', 'typed');
        $request = new ServerRequest('POST', '/', [], '1');
        $response = $invoker->handle($request);
        $this->assertSame('2', (string) $response->getBody());
    }

    public function testCloudEventInvokerDeclarative(): void
    {
        InvokerTest::$cloudeventResponse = "bye";
        FunctionsFramework::cloudEvent('helloCloudEvent', function (CloudEventInterface $cloudevent) {
            InvokerTest::$cloudeventResponse = "Hello CloudEvent!";
        });

        // signature type ignored due to declarative signature
        $invoker = new Invoker('helloCloudEvent', 'http');
        $request = new ServerRequest(
            'POST',
            '',
            [],
            '{"eventId":"foo","eventType":"bar","resource":"baz"}'
        );
        $invoker->handle($request);
        $this->assertSame('Hello CloudEvent!', InvokerTest::$cloudeventResponse);
    }

    public function testMultipleDeclarative(): void
    {
        FunctionsFramework::http('helloHttp', function (ServerRequestInterface $request) {
            return "Hello HTTP!";
        });
        FunctionsFramework::http('helloHttp2', function (ServerRequestInterface $request) {
            return "Hello HTTP 2!";
        });
        FunctionsFramework::http('helloHttp3', function (ServerRequestInterface $request) {
            return "Hello HTTP 3!";
        });
        // signature type ignored due to declarative signature
        $invoker = new Invoker('helloHttp2', 'cloudevent');
        $response = $invoker->handle();
        $this->assertSame('Hello HTTP 2!', (string) $response->getBody());
    }

    /**
     * @dataProvider provideErrorHandling
     */
    public function testErrorHandling($signatureType, $errorStatus, $request = null): void
    {
        $functionName = sprintf('invoke%sError', ucwords($signatureType));
        $invoker = new Invoker([$this, $functionName], $signatureType);
        // use a custom error log func
        $message = null;
        $invoker->setErrorLogger(function (string $error) use (&$message) {
            $message = $error;
        });

        // Invoke the handler
        $response = $invoker->handle($request);

        // Verify the error message response
        $this->assertEmpty((string) $response->getBody());
        $this->assertSame(500, $response->getStatusCode());
        $this->assertTrue(
            $response->hasHeader(FunctionWrapper::FUNCTION_STATUS_HEADER)
        );
        $this->assertSame(
            $errorStatus,
            $response->getHeaderLine(FunctionWrapper::FUNCTION_STATUS_HEADER)
        );
        // Verify the log output
        $this->assertNotNull($message);
        $this->assertStringContainsString('Exception: This is an error', $message);
        $this->assertStringContainsString('InvokerTest.php', $message); // stack trace
    }

    public function testTypedBadRequestError(): void
    {
        FunctionsFramework::typed('parseError', function (NotParseable $request) {
            throw new LogicException("should not get here");
        });

        $invoker = new Invoker('parseError', 'typed');
        $invoker->setErrorLogger(function () {
            ; // Log nothing in tests.
        });

        $request = new ServerRequest('POST', '/', [], '');
        $response = $invoker->handle($request);
        $this->assertSame('400', (string) $response->getStatusCode());
        $this->assertSame('Bad Request', (string) $response->getBody());
    }

    public static function provideErrorHandling(): array
    {
        return [
            ['http', 'crash'],
            ['cloudevent', 'error', new ServerRequest(
                'POST',
                '',
                [],
                '{"eventId":"foo","eventType":"bar","resource":"baz"}'
            )],
            ['typed', 'error'],
        ];
    }

    public function invokeThis(ServerRequestInterface $request): string
    {
        return 'Invoked!';
    }

    public function invokeHttpError(ServerRequestInterface $request): void
    {
        throw new Exception('This is an error');
    }

    public function invokeCloudeventError(CloudEvent $event): void
    {
        throw new Exception('This is an error');
    }

    public function invokeTypedError(IntValue $event): void
    {
        throw new Exception('This is an error');
    }
}
