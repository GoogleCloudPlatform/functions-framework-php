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

/**
 * To use this, set the following environment variables:
 *   FUNCTION_TARGET=helloHttp
 *   FUNCTION_EVENT_TYPE=http
 */

use Psr\Http\Message\ServerRequestInterface;

function helloHttp(ServerRequestInterface $request)
{
    return sprintf(
        "Hello %s from PHP HTTP function!" . PHP_EOL,
        $request->getQueryParams()['name'] ?? 'World'
    );
}

/**
 * To use this, set the following environment variables:
 *   FUNCTION_TARGET=helloCloudEvent
 *   FUNCTION_EVENT_TYPE=cloudevent
 */

use Google\CloudFunctions\CloudEvent;

function helloCloudEvent(CloudEvent $cloudevent)
{
    $stdout = fopen('php://stdout', 'wb');
    fwrite($stdout, $cloudevent);
}
