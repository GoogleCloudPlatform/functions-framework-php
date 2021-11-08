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

use CloudEvents\V1\CloudEventInterface;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class CloudEventFunctionWrapper extends FunctionWrapper
{
    private const TYPE_LEGACY = 1;
    private const TYPE_BINARY = 2;
    private const TYPE_STRUCTURED = 3;

    private bool $marshalToCloudEventInterface;

    // These are CloudEvent context attribute names that map to binary mode
    // HTTP headers when prefixed with 'ce-'. 'datacontenttype' is notably absent
    // from this list because the header 'ce-datacontenttype' is not permitted;
    // that data comes from the 'Content-Type' header instead. For more info see
    // https://github.com/cloudevents/spec/blob/v1.0.1/http-protocol-binding.md#311-http-content-type
    private static $binaryModeHeaderAttrs = [
        'id',
        'source',
        'specversion',
        'type',
        'dataschema',
        'subject',
        'time'
    ];

    public function __construct(callable $function, bool $marshalToCloudEventInterface = false)
    {
        $this->marshalToCloudEventInterface = $marshalToCloudEventInterface;
        parent::__construct($function);
    }

    public function execute(ServerRequestInterface $request): ResponseInterface
    {
        $body = (string) $request->getBody();
        $eventType = $this->getEventType($request);
        // We expect JSON if the content-type ends in "json" or if the event
        // type is legacy or structured Cloud Event.
        $shouldValidateJson = in_array($eventType, [
            self::TYPE_LEGACY,
            self::TYPE_STRUCTURED
        ]) || 'json' === substr($request->getHeaderLine('content-type'), -4);

        if ($shouldValidateJson) {
            $data = json_decode($body, true);

            // Validate JSON, return 400 Bad Request on error
            if (json_last_error() != JSON_ERROR_NONE) {
                return new Response(400, [
                    self::FUNCTION_STATUS_HEADER => 'crash'
                ], sprintf(
                    'Could not parse CloudEvent: %s',
                    '' !== $body ? json_last_error_msg() : 'Missing cloudevent payload'
                ));
            }
        } else {
            $data = $body;
        }

        switch ($this->getEventType($request)) {
            case self::TYPE_LEGACY:
                $mapper = new LegacyEventMapper();
                $cloudevent = $mapper->fromJsonData($data, $request->getUri()->getPath());
                break;

            case self::TYPE_STRUCTURED:
                $cloudevent = CloudEvent::fromArray($data);
                break;

            case self::TYPE_BINARY:
                $cloudevent = $this->fromBinaryRequest($request, $data);
                break;

            default:
                return new Response(400, [
                    self::FUNCTION_STATUS_HEADER => 'crash'
                ], 'invalid event type');
        }

        if ($this->marshalToCloudEventInterface) {
            $cloudevent = new CloudEventSdkCompliant($cloudevent);
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
        $data // mixed, can be "string" or "array"
    ): CloudEvent {
        $content = [];

        foreach (self::$binaryModeHeaderAttrs as $attr) {
            $ceHeader = 'ce-' . $attr;
            if ($request->hasHeader($ceHeader)) {
                $content[$attr] = $request->getHeaderLine($ceHeader);
            }
        }
        $content['data'] = $data;

        // For binary mode events the 'Content-Type' header corresponds to the
        // 'datacontenttype' attribute. There is no 'ce-datacontenttype' header.
        if ($request->hasHeader('content-type')) {
            $content['datacontenttype'] = $request->getHeaderLine('content-type');
        }

        return CloudEvent::fromArray($content);
    }

    protected function getFunctionParameterClassName(): string
    {
        return $this->marshalToCloudEventInterface ? CloudEventInterface::class : CloudEvent::class;
    }
}
