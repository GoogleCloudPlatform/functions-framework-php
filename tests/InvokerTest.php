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

use Exception;
use Google\CloudFunctions\CloudEvent;
use Google\CloudFunctions\Invoker;
use Google\CloudFunctions\FunctionWrapper;
use GuzzleHttp\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use ReflectionClass;

/**
 * @group gcf-framework
 */
class InvokerTest extends TestCase
{
    public function testInvalidSignatureType()
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('Invalid signature type: "invalid-signature-type"');
        new Invoker([$this, 'invokeThis'], 'invalid-signature-type');
    }

    public function testHttpInvoker()
    {
        $invoker = new Invoker([$this, 'invokeThis'], 'http');
        $response = $invoker->handle();
        $this->assertSame('Invoked!', (string) $response->getBody());
    }

    /**
     * @dataProvider provideErrorHandling
     */
    public function testErrorHandling($signatureType, $errorStatus, $request = null)
    {
        $functionName = sprintf('invoke%sError', ucwords($signatureType));
        $invoker = new Invoker([$this, $functionName], $signatureType);
        // use a custom error log func
        $message = null;
        $newErrorLogFunc = function (string $error) use (&$message) {
            $message = $error;
        };
        $errorLogFuncProp = (new ReflectionClass($invoker))
            ->getProperty('errorLogFunc');
        $errorLogFuncProp->setAccessible(true);
        $errorLogFuncProp->setValue($invoker, $newErrorLogFunc);

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

    public function provideErrorHandling()
    {
        return [
            ['http', 'crash'],
            ['cloudevent', 'error', new ServerRequest(
                'POST',
                '',
                [],
                '{"eventId":"foo","eventType":"bar","resource":"baz"}'
            )],
        ];
    }

    public function invokeThis(ServerRequestInterface $request)
    {
        return 'Invoked!';
    }

    public function invokeHttpError(ServerRequestInterface $request)
    {
        throw new Exception('This is an error');
    }

    public function invokeCloudeventError(CloudEvent $event)
    {
        throw new Exception('This is an error');
    }
}
