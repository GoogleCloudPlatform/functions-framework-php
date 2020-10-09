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
        switch ($this->getEventType($request)) {
            case self::TYPE_LEGACY:
                $cloudevent = $this->fromLegacyEvent($request);
                break;

            case self::TYPE_STRUCTURED:
            case self::TYPE_BINARY:
                // no difference between structured or binary for now
                $cloudevent = $this->fromCloudEvent($request);
                break;

            default:
                throw new \LogicException('Invalid event type');
                break;
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

    private function parseJsonData(ServerRequestInterface $request)
    {
        // Get Body
        $body = (string) $request->getBody();

        $jsonData = json_decode($body, true);
        if (json_last_error() != JSON_ERROR_NONE) {
            throw new RuntimeException(sprintf(
                'Could not parse CloudEvent: %s',
                '' !== $body ? json_last_error_msg() : 'Missing cloudevent payload'
            ));
        }

        return $jsonData;
    }

    private function fromCloudEvent(
        ServerRequestInterface $request
    ): CloudEvent {
        $jsonData = $this->parseJsonData($request);

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

    private function fromLegacyEvent(
        ServerRequestInterface $request
    ): CloudEvent {
        list($context, $data) = $this->getLegacyEventContextAndData($request);

        $eventType = $context->getEventType();
        $resourceName = $context->getResourceName();

        $ceId = $context->getEventId();

        // mapped from eventType
        $ceType = $this->ceType($eventType);

        // from context/resource/service, or mapped from eventType
        $ceService = $context->getService() ?: $this->ceService($eventType);

        // mapped from eventType & resource name
        $ceSource = $this->ceSource($eventType, $ceService, $resourceName);

        $ceSubject = $this->ceSubject($eventType, $resourceName);

        $ceTime = $context->getTimestamp();

        return CloudEvent::fromArray([
            'id' => $ceId,
            'source' => $ceSource,
            'specversion' => '1.0',
            'type' => $ceType,
            'datacontenttype' => 'application/json',
            'dataschema' => null,
            'subject' => $ceSubject, // only present for storage events
            'time' => $ceTime,
            'data' => $data,
        ]);
    }

    private function getLegacyEventContextAndData(
        ServerRequestInterface $request
    ): array {
        $jsonData = $this->parseJsonData($request);

        $data = $jsonData['data'] ?? null;

        if (array_key_exists('context', $jsonData)) {
            $context = $jsonData['context'];
        } else {
            unset($jsonData['data']);
            $context = $jsonData;
        }

        $context = Context::fromArray($context);

        return [$context, $data];
    }

    private function ceType(string $eventType): string
    {
        $ceTypeMap = [
            'google.pubsub.topic.publish' => 'google.cloud.pubsub.topic.v1.messagePublished',
            'providers/cloud.pubsub/eventTypes/topic.publish' => 'google.cloud.pubsub.topic.v1.messagePublished',
            'google.storage.object.finalize' => 'google.cloud.storage.object.v1.finalized',
            'google.storage.object.delete' => 'google.cloud.storage.object.v1.deleted',
            'google.storage.object.archive' => 'google.cloud.storage.object.v1.archived',
            'google.storage.object.metadataUpdate' => 'google.cloud.storage.object.v1.metadataUpdated',
            'providers/cloud.firestore/eventTypes/document.write' => 'google.cloud.firestore.document.v1.written',
            'providers/cloud.firestore/eventTypes/document.create' => 'google.cloud.firestore.document.v1.created',
            'providers/cloud.firestore/eventTypes/document.update' => 'google.cloud.firestore.document.v1.updated',
            'providers/cloud.firestore/eventTypes/document.delete' => 'google.cloud.firestore.document.v1.deleted',
            'providers/firebase.auth/eventTypes/user.create' => 'google.firebase.auth.user.v1.created',
            'providers/firebase.auth/eventTypes/user.delete' => 'google.firebase.auth.user.v1.deleted',
            'providers/google.firebase.analytics/eventTypes/event.log' => 'google.firebase.analytics.log.v1.written',
            'providers/google.firebase.database/eventTypes/ref.create' => 'google.firebase.database.document.v1.created',
            'providers/google.firebase.database/eventTypes/ref.write' => 'google.firebase.database.document.v1.written',
            'providers/google.firebase.database/eventTypes/ref.update' => 'google.firebase.database.document.v1.updated',
            'providers/google.firebase.database/eventTypes/ref.delete' => 'google.firebase.database.document.v1.deleted',
            'providers/cloud.storage/eventTypes/object.change' => 'google.cloud.storage.object.v1.finalized',
        ];

        if (isset($ceTypeMap[$eventType])) {
            return $ceTypeMap[$eventType];
        }

        // Defaut to the legacy event type if no mapping is found
        return $eventType;
    }

    private function ceService(string $eventType): string
    {
        $ceServiceMap = [
            'providers/cloud.firestore/' => 'firestore.googleapis.com',
            'providers/google.firebase.analytics/' => 'firebase.googleapis.com',
            'providers/firebase.auth/' => 'firebase.googleapis.com',
            'providers/google.firebase.database/' => 'firebase.googleapis.com',
            'providers/cloud.pubsub/' => 'pubsub.googleapis.com',
            'providers/cloud.storage/' => 'storage.googleapis.com',
        ];

        foreach ($ceServiceMap as $prefix => $ceService) {
            if (0 === strpos($eventType, $prefix)) {
                return $ceService;
            }
        }

        // Defaut to the legacy event type if no service mapping is found
        return $eventType;
    }

    private function ceSource(
        string $eventType,
        string $service, string
        $resourceName
    ): string {
        if (0 === strpos($eventType, 'google.storage')) {
            if (null !== $pos = strpos($resourceName, '/objects/')) {
                $resourceName = substr($resourceName, 0, $pos);
            }
        }
        return sprintf('//%s/%s', $service, $resourceName);
    }

    private function ceSubject(string $eventType, string $resourceName): ?string
    {
        if (0 === strpos($eventType, 'google.storage')) {
            if (null !== $pos = strpos($resourceName, 'objects/')) {
                return substr($resourceName, $pos);
            }
        }
        return null;
    }

    protected function getFunctionParameterClassName(): string
    {
        return CloudEvent::class;
    }
}
