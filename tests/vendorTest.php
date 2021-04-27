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
 * @runInSeparateProcess
 */
class vendorTest extends TestCase
{
    private static $tmpDir;

    public static function setUpBeforeClass(): void
    {
        if ('true' === getenv('SKIP_EXAMPLE_TESTS')) {
            self::markTestSkipped('Explicitly skipping the example tests');
        }

        $tmpDir = sprintf('%s/ff-php-test-%s', sys_get_temp_dir(), rand());
        mkdir($tmpDir);
        chdir($tmpDir);
        echo "Running tests in $tmpDir\n";

        // Copy Fixtures
        file_put_contents('composer.json', sprintf(
            file_get_contents(__DIR__ . '/fixtures/composer.json'),
            dirname(__DIR__)
        ));
        passthru('composer install');

        self::$tmpDir = $tmpDir;
    }

    public function testDefaultFunctionSource()
    {
        copy(__DIR__ . '/fixtures/index.php', self::$tmpDir . '/index.php');
        $cmd = sprintf(
            'FUNCTION_SOURCE=' .
            ' FUNCTION_SIGNATURE_TYPE=http' .
            ' FUNCTION_TARGET=helloDefault' .
            ' php %s/vendor/bin/router.php',
            self::$tmpDir
        );
        exec($cmd, $output);

        $this->assertSame(['Hello Default!'], $output);
    }

    public function testRelativeFunctionSource()
    {
        copy(__DIR__ . '/fixtures/relative.php', self::$tmpDir . '/relative.php');
        $cmd = sprintf(
            'FUNCTION_SOURCE=relative.php' .
            ' FUNCTION_SIGNATURE_TYPE=http' .
            ' FUNCTION_TARGET=helloDefault' .
            ' php %s/vendor/bin/router.php',
            self::$tmpDir
        );
        exec($cmd, $output);

        $this->assertSame(['Hello Relative!'], $output);
    }

    public function testAbsoluteFunctionSource()
    {
        copy(__DIR__ . '/fixtures/absolute.php', self::$tmpDir . '/absolute.php');
        $cmd = sprintf(
            'FUNCTION_SOURCE=%s/absolute.php' .
            ' FUNCTION_SIGNATURE_TYPE=http' .
            ' FUNCTION_TARGET=helloDefault' .
            ' php %s/vendor/bin/router.php',
            self::$tmpDir,
            self::$tmpDir
        );
        exec($cmd, $output);

        $this->assertSame(['Hello Absolute!'], $output);
    }

    public function testGcsIsNotRegistered()
    {
        copy(__DIR__ . '/fixtures/gcs.php', self::$tmpDir . '/gcs.php');
        $cmd = sprintf(
            'FUNCTION_SOURCE=%s/gcs.php' .
            ' FUNCTION_SIGNATURE_TYPE=http' .
            ' FUNCTION_TARGET=helloDefault' .
            ' php %s/vendor/bin/router.php',
            self::$tmpDir,
            self::$tmpDir
        );
        exec($cmd, $output);

        $this->assertEquals(['GCS Stream Wrapper is not registered'], $output);
    }

    /**
     * @depends testGcsIsNotRegistered
     */
    public function testGcsIsRegistered()
    {
        passthru('composer require google/cloud-storage');

        copy(__DIR__ . '/fixtures/gcs.php', self::$tmpDir . '/gcs.php');
        $cmd = sprintf(
            'FUNCTION_SOURCE=%s/gcs.php' .
            ' FUNCTION_SIGNATURE_TYPE=http' .
            ' FUNCTION_TARGET=helloDefault' .
            ' php %s/vendor/bin/router.php',
            self::$tmpDir,
            self::$tmpDir
        );
        exec($cmd, $output);

        $this->assertEquals(['GCS Stream Wrapper is registered'], $output);
    }
}
