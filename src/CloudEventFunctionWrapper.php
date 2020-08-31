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

class CloudEventFunctionWrapper extends FunctionWrapper
{
    public function execute(ServerRequestInterface $request): ResponseInterface
    {
        $body = (string) $request->getBody();
        $cloudevent_data = json_decode($body, true);
        if (json_last_error() != JSON_ERROR_NONE) {
            throw new RuntimeException(sprintf(
                'Could not parse CloudEvent: %s',
                '' !== $body ? json_last_error_msg() : 'Missing cloudevent payload'
            ));
        }
        
        $headers = $request->getHeaders();
        $cloudevent_content = [
            'data' => $cloudevent_data,
            'id' => $headers["ce-id"][0],
            'source' => $headers["ce-source"][0],
            'specversion' => $headers["ce-specversion"][0],
            'type' => $headers["ce-type"][0],
            'datacontenttype' => $headers["ce-datacontenttype"][0],
            'dataschema' => $headers["ce-dataschema"][0],
            'subject' => $headers["ce-subject"][0],
            'time' => $headers["ce-time"][0]
        ];
        $cloudevent = CloudEvent::fromArray($cloudevent_content);
        // echo "\n== START ==\n";
        // echo $cloudevent;
        // echo "\n== END ==\n";
        call_user_func($this->function, $cloudevent);
        return new Response();
    }
}
