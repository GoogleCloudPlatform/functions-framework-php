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

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Google\CloudFunctions\FunctionsFramework;

/**
 * @group gcf-framework
 * @runInSeparateProcess
 */
class routerTest extends TestCase
{
    public function testInvalidFunctionTarget(): void
    {
        $this->expectException('RuntimeException');
        $this->expectExceptionMessage('FUNCTION_TARGET is not set');
        putenv('FUNCTION_SOURCE=' . __DIR__ . '/../examples/hello/index.php');
        putenv('FUNCTION_TARGET');
        putenv('FUNCTION_SIGNATURE_TYPE=http');
        require 'router.php';
    }

    public function testDefaultFunctionSignatureType(): void
    {
        putenv('FUNCTION_SOURCE=' . __DIR__ . '/../examples/hello/index.php');
        putenv('FUNCTION_TARGET=Google\CloudFunctions\Tests\test_callable');
        require 'router.php';

        $this->expectOutputString('Invoked!');
    }

    public function testInvalidFunctionSource(): void
    {
        $this->expectException('RuntimeException');
        $this->expectExceptionMessage('Unable to load function from "doesnotexist.php"');
        putenv('FUNCTION_SOURCE=doesnotexist.php');
        require 'router.php';
    }

    public function testRouterInvokedSuccessfully(): void
    {
        putenv('FUNCTION_SOURCE=' . __DIR__ . '/../examples/hello/index.php');
        putenv('FUNCTION_TARGET=Google\CloudFunctions\Tests\test_callable');
        putenv('FUNCTION_SIGNATURE_TYPE=http');
        require 'router.php';

        $this->expectOutputString('Invoked!');
    }

    public function testCloudStorageStreamWrapperNotRegisteredByDefault(): void
    {
        $wrappers = stream_get_wrappers();
        require 'router.php';
        $this->assertEquals($wrappers, stream_get_wrappers());
        $this->assertNotContains('gs', stream_get_wrappers());
    }

    public function testDeclarativeOverNonDeclarative(): void
    {
        FunctionsFramework::http('helloHttp', function (ServerRequestInterface $request) {
            return 'Hello World!';
        });

        // index.php also has a non-declaration function named 'helloHttp'.
        putenv('FUNCTION_SOURCE=' . __DIR__ . '/../examples/hello/index.php');
        putenv('FUNCTION_TARGET=helloHttp');
        putenv('FUNCTION_SIGNATURE_TYPE=cloudevent'); // ignored due to declarative signature
        require 'router.php';

        // Expect the declarative function to be called.
        $this->expectOutputString('Hello World!');
    }
}

function test_callable(ServerRequestInterface $request)
{
    return 'Invoked!';
}
