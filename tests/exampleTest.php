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
 * Tests for when this package is installed in a vendored directory
 *
 * @group gcf-framework
 */
class exampleTest extends TestCase
{
    private static $containerId;
    private static $imageId;
    private static $client;

    public static function setUpBeforeClass(): void
    {
        if ('true' === getenv('SKIP_EXAMPLE_TESTS')) {
            self::markTestSkipped('Explicitly skipping the example tests');
        }

        $exampleDir = __DIR__ . '/../examples/hello';
        self::$imageId = 'test-image-' . time();

        // Remove lockfile to ensure docker deps are up-to-date
        if (file_exists($lockFile = sprintf('%s/composer.lock', $exampleDir))) {
            passthru('rm %s', $lockFile);
        }

        $cmd = sprintf('docker build %s -t %s', $exampleDir, self::$imageId);

        passthru($cmd, $output);

        self::$client = new Client([
            'base_uri' => 'http://localhost:8080',
            'http_errors' => false,
        ]);
    }

    public function testHttp(): void
    {
        $cmd = 'docker run -d -p 8080:8080 '
            . '-e FUNCTION_TARGET=helloHttp '
            . self::$imageId;

        exec($cmd, $output);
        self::$containerId = $output[0];

        // Tests fail if we do not wait before sending requests in
        sleep(1);

        $response = self::$client->get('/');
        $this->assertSame(
            'Hello World from PHP HTTP function!' . PHP_EOL,
            $response->getBody()->getContents()
        );

        $response = self::$client->get('/?name=Foo');
        $this->assertSame(
            'Hello Foo from PHP HTTP function!' . PHP_EOL,
            $response->getBody()->getContents()
        );

        passthru('docker rm -f ' . self::$containerId);
        self::$containerId = null;
    }

    public function testCloudEvent(): void
    {
        $cmd = 'docker run -d -t -p 8080:8080 '
            . '-e FUNCTION_TARGET=helloCloudEvent '
            . '-e FUNCTION_SIGNATURE_TYPE=cloudevent '
            . self::$imageId;

        exec($cmd, $output);
        self::$containerId = $output[0];

        // Tests fail if we do not wait before sending requests in
        sleep(1);

        $response = self::$client->request('POST', '/', [
            'headers' => [
                'ce-id' => '1234567890',
                'ce-source' => '//pubsub.googleapis.com/projects/MY-PROJECT/topics/MY-TOPIC',
                'ce-specversion' => '1.0',
                'ce-type' => 'com.google.cloud.pubsub.topic.publish',
                'content-type' => 'application/json',
            ],
            'json' => ['foo' => 'bar']
        ]);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertEmpty($response->getBody()->getContents());

        exec('docker logs ' . self::$containerId, $output);

        $outputAsString = implode("\n", $output);
        $this->assertStringContainsString('- id: 1234567890', $outputAsString);
        $this->assertStringContainsString(
            '- type: com.google.cloud.pubsub.topic.publish',
            $outputAsString
        );
        $this->assertStringContainsString(
            '- source: //pubsub.googleapis.com/projects/MY-PROJECT/topics/MY-TOPIC"',
            $outputAsString
        );

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
