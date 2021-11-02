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

use Exception;
use InvalidArgumentException;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ServerRequest;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class Invoker
{
    private $function;
    private $errorLogFunc;

    /**
     * @param array|string $target      The callable to be invoked
     */
    public function __construct($target)
    {
        $signatureType = getenv('FUNCTION_SIGNATURE_TYPE', true) ?: 'http';

        $isDeclarativeSignature = false;
        if (is_string($target) && Registry::contains($target)) {
            $isDeclarativeSignature = true;
            $signatureType = Registry::getFunctionType($target);
            $target = Registry::getFunctionHandle($target);
        }

        if (!is_callable($target)) {
            throw new InvalidArgumentException(sprintf(
                'Function target is not callable: "%s"',
                $target
            ));
        }

        if ($signatureType === 'http') {
            $this->function = new HttpFunctionWrapper($target);
        } elseif (
            $signatureType === 'event'
            || $signatureType === 'cloudevent'
        ) {
            $this->function = new CloudEventFunctionWrapper($target, $isDeclarativeSignature);
        } else {
            throw new InvalidArgumentException(sprintf(
                'Invalid signature type: "%s"',
                $signatureType
            ));
        }
        $this->errorLogFunc = function (string $error) {
            fwrite(fopen('php://stderr', 'wb'), json_encode([
                'message' => $error,
                'severity' => 'error'
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        };
    }

    public function handle(
        ServerRequestInterface $request = null
    ): ResponseInterface {
        if ($request === null) {
            $request = ServerRequest::fromGlobals();
        }

        try {
            return $this->function->execute($request);
        } catch (Exception $e) {
            // Log the full error and stack trace
            ($this->errorLogFunc)((string) $e);
            // Set "X-Google-Status" to "crash" for Http functions and "error"
            // for Cloud Events
            $statusHeader = $this->function instanceof HttpFunctionWrapper
                ? 'crash'
                : 'error';
            return new Response(500, [
                FunctionWrapper::FUNCTION_STATUS_HEADER => $statusHeader,
            ]);
        }
    }
}
