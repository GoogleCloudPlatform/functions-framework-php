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

class CloudEventFunctionWrapper extends FunctionWrapper
{
    private const TYPE_LEGACY = 1;
    private const TYPE_BINARY = 2;
    private const TYPE_STRUCTURED = 3;

    private static $validKeys = [
        'id',
        'source',
        'specversion',
        'type',
        'datacontenttype',
        'dataschema',
        'subject',
        'time'
    ];

    public function execute(ServerRequestInterface $request): ResponseInterface
    {
        $body = (string) $request->getBody();
        $jsonData = json_decode($body, true);

        // Validate JSON, return 400 Bad Request on error
        if (json_last_error() != JSON_ERROR_NONE) {
            return new Response(400, [
                self::FUNCTION_STATUS_HEADER => 'crash'
            ], sprintf(
                'Could not parse CloudEvent: %s',
                '' !== $body ? json_last_error_msg() : 'Missing cloudevent payload'
            ));
        }

        switch ($this->getEventType($request)) {
            case self::TYPE_LEGACY:
                $mapper = new LegacyEventMapper();
                $cloudevent = $mapper->fromJsonData($jsonData);
                break;

            case self::TYPE_STRUCTURED:
                $cloudevent = CloudEvent::fromArray($jsonData);
                break;

            case self::TYPE_BINARY:
                $cloudevent = $this->fromBinaryRequest($request, $jsonData);
                break;

            default:
                return new Response(400, [
                    self::FUNCTION_STATUS_HEADER => 'crash'
                ], 'invalid event type');
        }

        call_user_func($this->function, $cloudevent);
        return new Response();
    }

    private function getEventType(ServerRequestInterface $request)
    {
        if (
            $request->hasHeader('ce-type')
            && $request->hasHeader('ce-specversion')
            && $request->hasHeader('ce-source')
            && $request->hasHeader('ce-id')
        ) {
            return self::TYPE_BINARY;
        } elseif ($request->getHeaderLine('content-type') === 'application/cloudevents+json') {
            return self::TYPE_STRUCTURED;
        } else {
            return self::TYPE_LEGACY;
        }
    }

    private function fromBinaryRequest(
        ServerRequestInterface $request,
        $jsonData
    ): CloudEvent {
        $content = [];

        foreach (self::$validKeys as $key) {
            $ceKey = 'ce-' . $key;
            if ($request->hasHeader($ceKey)) {
                $content[$key] = $request->getHeaderLine($ceKey);
            }
        }
        $content['data'] = $jsonData;
        return CloudEvent::fromArray($content);
    }

    protected function getFunctionParameterClassName(): string
    {
        return CloudEvent::class;
    }
}
