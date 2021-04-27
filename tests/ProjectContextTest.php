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

use Google\CloudFunctions\ProjectContext;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use RuntimeException;

/**
 * @group gcf-framework
 */
class ProjectContextTest extends TestCase
{
    public function testLocateAutoloadFile()
    {
        $context = new ProjectContext();
        $autoloadFile = $context->locateAutoloadFile();
        $this->assertNotNull($autoloadFile);
        $this->assertTrue(file_exists($autoloadFile));
    }

    public function testLocateFunctionSource()
    {
        $context = new ProjectContext();
        $source = $context->locateFunctionSource(null);
        $this->assertNull($source, 'No detectable source for git clones');
    }

    public function testLocateFunctionSourceAbsolute()
    {
        $context = new ProjectContext();
        $suppliedSource = __FILE__;
        $source = $context->locateFunctionSource($suppliedSource);

        $this->assertSame($suppliedSource, $source);
    }

    public function testLocateFunctionSourceRelative()
    {
        $context = new ProjectContext();

        // Set value of document root for testing
        $reflection = new ReflectionClass($context);
        $docRoot = $reflection->getProperty('documentRoot');
        $docRoot->setAccessible(true);
        $docRoot->setValue($context, __DIR__ . '/../');

        $suppliedSource = 'tests/ProjectContextTest.php';
        $source = $context->locateFunctionSource($suppliedSource);

        $this->assertNotSame($suppliedSource, $source);
        $this->assertSame(realpath($suppliedSource), realpath($source));
    }

    public function testNonexistantFunctionSource()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'Unable to load function from "nonexistant.php"'
        );

        $context = new ProjectContext();
        $context->locateFunctionSource('nonexistant.php');
    }
}
