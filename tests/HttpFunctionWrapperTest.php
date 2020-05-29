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

use Google\CloudFunctions\HttpFunctionWrapper;
use GuzzleHttp\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @group gcf-framework
 */
class HttpFunctionWrapperTest extends TestCase
{
    public function testHttpHttpFunctionWrapper()
    {
        $httpFunctionWrapper = new HttpFunctionWrapper([$this, 'invokeThis']);
        $request = new ServerRequest('GET', '/');
        $response = $httpFunctionWrapper->execute($request);
        $this->assertEquals((string) $response->getBody(), 'Invoked!');
    }

    public function testHttpErrorPaths()
    {
        $httpFunctionWrapper = new HttpFunctionWrapper([$this, 'invokeThis']);
        $request = new ServerRequest('GET', '/robots.txt');
        $response = $httpFunctionWrapper->execute($request);
        $this->assertEquals($response->getStatusCode(), 404);
        $this->assertEquals('', (string) $response->getBody());
        $request = new ServerRequest('GET', '/favicon.ico');
        $response = $httpFunctionWrapper->execute($request);
        $this->assertEquals($response->getStatusCode(), 404);
        $this->assertEquals('', (string) $response->getBody());
    }

    public function invokeThis(ServerRequestInterface $request)
    {
        return 'Invoked!';
    }
}
