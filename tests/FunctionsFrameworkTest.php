<?php
/**
 * Copyright 2021 Google LLC.
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

use Google\CloudFunctions\FunctionsFramework;
use Google\CloudFunctions\FunctionsFrameworkTesting;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use CloudEvents\V1\CloudEventInterface;
use Google\CloudFunctions\Tests\Common\IntValue;

/**
 * @group gcf-framework
 */
class FunctionsFrameworkTest extends TestCase
{
    public function testRegisterAndRetrieveHttpFunction(): void
    {
        $fn = function (ServerRequestInterface $request) {
            return "this is a test function";
        };

        FunctionsFramework::http('testFn', $fn);

        $this->assertEquals(
            $fn,
            FunctionsFrameworkTesting::getRegisteredFunction('testFn')
        );
    }

    public function testRegisterAndRetrieveCloudEventFunction(): void
    {
        $fn = function (CloudEventInterface $event) {
            return "this is a test function";
        };

        FunctionsFramework::cloudEvent('testFn', $fn);

        $this->assertEquals(
            $fn,
            FunctionsFrameworkTesting::getRegisteredFunction('testFn')
        );
    }

    public function testRegisterAndRetrieveTypedFunction(): void
    {
        $fn = function (IntValue $event) {
            return $event;
        };

        FunctionsFramework::typed('testFn', $fn);

        $this->assertEquals(
            $fn,
            FunctionsFrameworkTesting::getRegisteredFunction('testFn')
        );
    }

    public function testRetrieveNonexistantFunction(): void
    {
        $this->assertNull(
            FunctionsFrameworkTesting::getRegisteredFunction('thisDoesntExist')
        );
    }
}
