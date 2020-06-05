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

use PHPUnit\Framework\TestCase;

/**
 * Tests for when this package is installed in a vendored directory
 *
 * @group gcf-framework
 */
class exampleTest extends TestCase
{
    private static $containerId;
    private static $imageId;

    public static function setUpBeforeClass(): void
    {
        if ('true' === getenv('TRAVIS')) {
            self::markTestSkipped('These tests do not pass on travis');
        }

        $exampleDir = __DIR__ . '/../examples/hello';
        self::$imageId = 'test-image-' . time();

        $cmd = 'composer update -d ' . $exampleDir;

        passthru($cmd, $output);

        $cmd = sprintf('docker build %s -t %s', $exampleDir, self::$imageId);

        passthru($cmd, $output);

        $cmd = 'docker run -d -p 8080:8080 '
            . '-e FUNCTION_TARGET=helloHttp '
            . self::$imageId;

        exec($cmd, $output);
        self::$containerId = $output[0];
        // Tests fail if we do not wait before sending requests in
        sleep(1);
    }

    public function testIndex(): void
    {
        exec('curl -v http://localhost:8080', $output);
        $this->assertContains('Hello World from PHP HTTP function!', $output);
    }

    public function testIndexWithQuery(): void
    {
        exec('curl -v http://localhost:8080?name=Foo', $output);
        $this->assertContains('Hello Foo from PHP HTTP function!', $output);
    }

    public static function tearDownAfterClass(): void
    {
        if (self::$containerId) {
            passthru('docker rm -f ' . self::$containerId);
            passthru('docker rmi -f ' . self::$imageId);
        }
    }
}
