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

use Google\CloudFunctions\Context;
use PHPUnit\Framework\TestCase;

/**
 * @group gcf-framework
 */
class ContextTest extends TestCase
{
    public function testFromArray(): void
    {
        $context = Context::fromArray([
            'eventId' => 'abc',
            'timestamp' => 'def',
            'eventType' => 'ghi',
            'resource' => ['name' => 'jkl', 'service' => 'mno'],
        ]);
        $this->assertSame('abc', $context->getEventId());
        $this->assertSame('def', $context->getTimestamp());
        $this->assertSame('ghi', $context->getEventType());
        $this->assertSame([
            'name' => 'jkl',
            'service' => 'mno'
        ], $context->getResource());
        $this->assertSame('jkl', $context->getResourceName());
        $this->assertSame('mno', $context->getService());
    }

    public function testFromEmptyArray(): void
    {
        $context = Context::fromArray([]);
        $this->assertNull($context->getEventId());
        $this->assertNull($context->getTimestamp());
        $this->assertNull($context->getEventType());
        $this->assertNull($context->getResource());
        $this->assertNull($context->getResourceName());
        $this->assertNull($context->getService());
    }
}
