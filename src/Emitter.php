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

use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;

class Emitter
{
    public function emit(ResponseInterface $response): void
    {
        // Only send headers if they have not already been sent
        if (!headers_sent()) {
            $this->statusLine();
            $this->headers();
        }

        // Send the body.
        echo $response->getBody();
    }

    private function statusLine(ResponseInterface $response) : void
    {
        $statusCode = $response->getStatusCode();
        $statusLine = sprintf(
            'HTTP/%s %s %s',
            $response->getProtocolVersion(),
            $statusCode,
            $response->getReasonPhrase()
        );
        header($statusLine, true, $statusCode);
    }

    private function headers(ResponseInterface $response) : void
    {
        $statusCode = $response->getStatusCode();

        foreach ($response->getHeaders() as $header => $values) {
            $name = ucwords($header, '-');
            $isCookie = $name === 'Set-Cookie';
            $first = true;
            foreach ($values as $value) {
                // Replace headers for first value only, except for cookies
                $replace = $first && !$isCookie;
                $first = false;
                header($name . ':' . $value, $replace, $statusCode);
            }
        }
    }
}
