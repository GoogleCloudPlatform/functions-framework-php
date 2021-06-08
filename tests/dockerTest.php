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
use GuzzleHttp\Client;

/**
 * Tests for when this framework is run in a docker container
 *
 * @group gcf-framework
 * @runInSeparateProcess
 */
class dockerTest extends TestCase
{
    private static $containerId;
    private static $imageId;
    private static $client;

    public static function setUpBeforeClass(): void
    {
        if ('true' === getenv('SKIP_EXAMPLE_TESTS')) {
            self::markTestSkipped('Explicitly skipping the example tests');
        }

        mkdir($tmpDir = sys_get_temp_dir() . '/ff-php-test-' . rand());

        // Copy Fixtures
        copy(__DIR__ . '/fixtures/docker.php', $tmpDir . '/index.php');
        copy(__DIR__ . '/../examples/hello/Dockerfile', $tmpDir . '/Dockerfile');
        copy(__DIR__ . '/../examples/hello/composer.json', $tmpDir . '/composer.json');

        self::$imageId = 'test-image-' . time();

        $cmd = sprintf('docker build %s -t %s', $tmpDir, self::$imageId);

        passthru($cmd, $output);

        self::$client = new Client([
            'base_uri' => 'http://localhost:8080',
            'http_errors' => false,
        ]);
    }

    public function testHttpStatusCode(): void
    {
        $cmd = 'docker run -d -p 8080:8080 '
            . '-e FUNCTION_TARGET=testStatusCode '
            . self::$imageId;

        exec($cmd, $output);

        self::$containerId = $output[0];

        // Tests fail if we do not wait before sending requests in
        sleep(1);

        $response = self::$client->get('/');

        $this->assertSame(418, $response->getStatusCode());
        $this->assertSame("I'm a teapot", $response->getReasonPhrase());

        passthru('docker rm -f ' . self::$containerId);
        self::$containerId = null;
    }

    public static function tearDownAfterClass(): void
    {
        // If a test failed before it could delete its container
        if (self::$containerId) {
            passthru('docker rm -f ' . self::$containerId);
        }
        // Remove the test image
        if (self::$imageId) {
            passthru('docker rmi -f ' . self::$imageId);
        }
    }
}
