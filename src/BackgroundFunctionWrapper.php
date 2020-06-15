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

class BackgroundFunctionWrapper extends FunctionWrapper
{
    public function __construct(callable $function)
    {
        parent::__construct($function);
    }

    public function execute(ServerRequestInterface $request): ResponseInterface
    {
        $body = (string) $request->getBody();
        $event = json_decode($body, true);
        if (json_last_error() != JSON_ERROR_NONE) {
            throw new RuntimeException(sprintf(
                'Could not parse request body: %s',
                '' !== $body ? json_last_error_msg() : 'Missing event payload'
            ));
        }

        $data = $event['data'];

        if (array_key_exists('context', $event)) {
            $context = $event['context'];
        } else {
            unset($event['data']);
            $context = $event;
        }

        $context = Context::fromArray($context);
        call_user_func($this->function, $data, $context);

        return new Response();
    }
}
