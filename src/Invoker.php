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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

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
        } elseif ($signatureType === 'event') {
            $this->function = new BackgroundFunctionWrapper($target);
        } else {
            throw new InvalidArgumentException(sprintf(
                'Invalid signature type: "%s"', $signatureType));
        }
    }

    public function handle(Request $request = null) : Response
    {
        if ($request === null) {
            $request = Request::createFromGlobals();
        }

        return $this->function->execute($request);
    }
}
