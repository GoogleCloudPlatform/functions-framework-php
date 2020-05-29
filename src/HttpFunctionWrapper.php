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

namespace Google\CloudFunctions;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use GuzzleHttp\Psr7\Response;

class HttpFunctionWrapper extends FunctionWrapper
{
    public function __construct(callable $function)
    {
        parent::__construct($function);
    }

    public function execute(ServerRequestInterface $request): ResponseInterface
    {
        $path = $request->getUri()->getPath();
        if ($path == '/robots.txt' || $path == '/favicon.ico') {
            return new Response(404);
        }

        $response = call_user_func($this->function, $request);

        if (is_string($response)) {
            $response = new Response(200, [], $response);
        }

        return $response;
    }
}
