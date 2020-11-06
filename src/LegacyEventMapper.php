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

class LegacyEventMapper
{
    public function fromJsonData(array $jsonData): CloudEvent
    {
        list($context, $data) = $this->getLegacyEventContextAndData($jsonData);

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

    private function getLegacyEventContextAndData(array $jsonData): array
    {
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
}
