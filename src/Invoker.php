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

use InvalidArgumentException;
use GuzzleHttp\Psr7\ServerRequest;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class Invoker
{
    private $function;

    /**
     * @param $target callable The callable to be invoked
     * @param $signatureType The signature type of the target callable, either
     *                       "event" or "http".
     */
    public function __construct(callable $target, string $signatureType)
    {
        if ($signatureType === 'http') {
            $this->function = new HttpFunctionWrapper($target);
        } elseif (
            $signatureType === 'event'
            || $signatureType === 'cloudevent'
        ) {
            $this->function = new CloudEventFunctionWrapper($target);
        } else {
            throw new InvalidArgumentException(sprintf(
                'Invalid signature type: "%s"', $signatureType));
        }
    }

    public function handle(
        ServerRequestInterface $request = null
    ) : ResponseInterface {
        if ($request === null) {
            $request = ServerRequest::fromGlobals();
        }

        return $this->function->execute($request);
    }
}
