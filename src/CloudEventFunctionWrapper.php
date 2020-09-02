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

namespace Google\CloudFunctions;

use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;

class CloudEventFunctionWrapper extends FunctionWrapper
{
    public function execute(ServerRequestInterface $request): ResponseInterface
    {
        // Get Body
        $body = (string) $request->getBody();
        $cloudeventData = json_decode($body, true);
        if (json_last_error() != JSON_ERROR_NONE) {
            throw new RuntimeException(sprintf(
                'Could not parse CloudEvent: %s',
                '' !== $body ? json_last_error_msg() : 'Missing cloudevent payload'
            ));
        }
        
        // Get Headers
        $headers = $request->getHeaders();
        $cloudeventContent = [];
        $validKeys = ['id', 'source', 'specversion', 'type', 'datacontenttype', 'dataschema', 'subject', 'time'];
        foreach ($validKeys as $key) {
            $ceKey = 'ce-' . $key;
            if (isset($headers[$ceKey])) {
                $cloudeventContent[$key] = $headers[$ceKey][0];
            }
        }
        $cloudeventContent['data'] = $cloudeventData;
        $cloudevent = CloudEvent::fromArray($cloudeventContent);
        call_user_func($this->function, $cloudevent);
        return new Response();
    }
}
