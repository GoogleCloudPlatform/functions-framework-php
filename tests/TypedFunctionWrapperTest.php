<?php

/**
 * Copyright 2023 Google LLC.
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

require_once 'tests/common/types.php';

use Google\CloudFunctions\HttpFunctionWrapper;
use Google\CloudFunctions\Tests\Common\BadType;
use Google\CloudFunctions\Tests\Common\IntValue;
use Google\CloudFunctions\Tests\Common\NotParseable;
use Google\CloudFunctions\TypedFunctionWrapper;
use GuzzleHttp\Psr7\ServerRequest;
use LogicException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @group gcf-framework
 */
class TypedFunctionWrapperTest extends TestCase
{
    public function testNoFunctionParameters(): void
    {
        $this->expectException('LogicException');
        $this->expectExceptionMessage(
            'Your function must declare exactly one required parameter that has a valid type hint'
        );
        $request = new ServerRequest('POST', '/', []);
        $typedFunctionWrapper = new TypedFunctionWrapper(
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
        $typedFunctionWrapper = new TypedFunctionWrapper(
            function (ServerRequestInterface $foo, $bar) {
            }
        );
    }

    public function testNoTypehintInFunctionParameter(): void
    {
        $this->expectException('LogicException');
        $this->expectExceptionMessage(
            'Your function must declare exactly one required parameter that has a valid type hint'
        );
        $typedFunctionWrapper = new TypedFunctionWrapper(
            function ($foo) {
            }
        );
    }

    public function testTypehintClassDoesNotExist(): void
    {
        $this->expectException('LogicException');
        $this->expectExceptionMessage(
            'Could not find function parameter type Google\CloudFunctions\Tests\NotTheRightThing'
        );
        $typedFunctionWrapper = new TypedFunctionWrapper(
            function (NotTheRightThing $foo) {
            }
        );
        $this->assertTrue(true, 'No exception was thrown');
    }

    public function testTypehintClassDoesNotSatisfyContract(): void
    {
        $this->expectException('Error');
        $this->expectExceptionMessage(
            'Call to undefined method Google\CloudFunctions\Tests\Common\BadType::mergeFromJsonString()'
        );

        $typedFunctionWrapper = new TypedFunctionWrapper(
            function (BadType $foo) {
            }
        );
        $request = new ServerRequest('POST', '/', ['content-type' => 'application/json'], 'notjson');

        $typedFunctionWrapper->execute($request);
    }

    public function testClassTypedFunction(): void
    {
        $typedFunctionWrapper = new TypedFunctionWrapper(
            function (IntValue $foo): IntValue {
                return new IntValue($foo->value + 1);
            }
        );
        $request = new ServerRequest('POST', '/', ['content-type' => 'application/json'], '1');
        $response = $typedFunctionWrapper->execute($request);
        $this->assertSame('2', (string) $response->getBody());
    }

    public function testBadRequest(): void
    {
        $this->expectException('Google\CloudFunctions\ParseError');
        $this->expectExceptionMessage(
            'could not parse'
        );

        $typedFunctionWrapper = new TypedFunctionWrapper(
            function (NotParseable $unused) {
                throw new LogicException("should not get here");
            }
        );
        $request = new ServerRequest('POST', '/', ['content-type' => 'application/json'], 'notjson');
        $response = $typedFunctionWrapper->execute($request);
    }
}
