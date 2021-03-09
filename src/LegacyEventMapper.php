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
    // Maps background/legacy event types to their equivalent CloudEvent types.
    // For more info on event mappings see
    // https://github.com/GoogleCloudPlatform/functions-framework-conformance/blob/master/docs/mapping.md
    private $ceTypeMap = [
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

    // CloudEvent service names.
    private $firebaseAuthCEService = 'firebaseauth.googleapis.com';
    private $firebaseCEService = 'firebase.googleapis.com';
    private $firebaseDBCEService = 'firebasedatabase.googleapis.com';
    private $firestoreCEService = 'firestore.googleapis.com';
    private $pubSubCEService = 'pubsub.googleapis.com';
    private $storageCEService = 'storage.googleapis.com';

    // Maps background event services to their equivalent CloudEvent services.
    private $ceServiceMap;

    // Maps CloudEvent service strings to regular expressions used to split a background
    // event resource string into CloudEvent resource and subject strings. Each regex
    // must have exactly two capture groups: the first for the resource and the second
    // for the subject.
    private $ceResourceRegexMap;

    // Maps Firebase Auth background event metadata field names to their equivalent
    // CloudEvent field names.
    private $firebaseAuthMetadataFieldMap = [
        'createdAt' => 'createTime',
        'lastSignedInAt' => 'lastSignInTime',
    ];

    public function __construct()
    {
        $this->ceServiceMap = [
            'providers/cloud.firestore/' => $this->firestoreCEService,
            'providers/google.firebase.analytics/' => $this->firebaseCEService,
            'providers/firebase.auth/' => $this->firebaseAuthCEService,
            'providers/google.firebase.database/' => $this->firebaseDBCEService,
            'providers/cloud.pubsub/' => $this->pubSubCEService,
            'providers/cloud.storage/' => $this->storageCEService,
        ];

        $this->ceResourceRegexMap = [
            $this->firebaseCEService => '#^(projects/[^/]+)/(events/[^/]+)$#',
            $this->firebaseDBCEService => '#^(projects/_/instances/[^/]+)/(refs/.+)$#',
            $this->firestoreCEService => '#^(projects/[^/]+/databases/\(default\))/(documents/.+)$#',
            $this->storageCEService => '#^(projects/_/buckets/[^/]+)/(objects/.+)$#',
        ];
    }

    public function fromJsonData(array $jsonData): CloudEvent
    {
        list($context, $data) = $this->getLegacyEventContextAndData($jsonData);

        $eventType = $context->getEventType();
        $resourceName = $context->getResourceName();

        $ceId = $context->getEventId();

        // Mapped from eventType.
        $ceType = $this->ceType($eventType);

        // From context/resource/service, or mapped from eventType.
        $ceService = $context->getService() ?: $this->ceService($eventType);

        // Split the background event resource into a CloudEvent resource and subject.
        $resourceAndSubject = $this->ceResourceAndSubject($ceService, $resourceName);
        $ceResource = $resourceAndSubject[0];
        $ceSubject = $resourceAndSubject[1];

        $ceTime = $context->getTimestamp();

        // Handle Pub/Sub events.
        if ($ceService === $this->pubSubCEService) {
            $data = ['message' => $data];
        }

        // Handle Firebase Auth events.
        if ($ceService === $this->firebaseAuthCEService) {
            if (array_key_exists('metadata', $data)) {
                foreach ($this->firebaseAuthMetadataFieldMap as $old => $new) {
                    if (array_key_exists($old, $data['metadata'])) {
                        $data['metadata'][$new] = $data['metadata'][$old];
                        unset($data['metadata'][$old]);
                    }
                }
            }

            if (array_key_exists('uid', $data)) {
                $ceSubject = sprintf('users/%s', $data['uid']);
            }
        }

        return CloudEvent::fromArray([
            'id' => $ceId,
            'source' => sprintf('//%s/%s', $ceService, $ceResource),
            'specversion' => '1.0',
            'type' => $ceType,
            'datacontenttype' => 'application/json',
            'dataschema' => null,
            'subject' => $ceSubject,
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
        if (isset($this->ceTypeMap[$eventType])) {
            return $this->ceTypeMap[$eventType];
        }

        // Default to the legacy event type if no mapping is found.
        return $eventType;
    }

    private function ceService(string $eventType): string
    {
        foreach ($this->ceServiceMap as $prefix => $ceService) {
            if (0 === strpos($eventType, $prefix)) {
                return $ceService;
            }
        }

        // Default to the legacy event type if no service mapping is found.
        return $eventType;
    }

    private function ceResourceAndSubject(string $ceService, string $resource): array
    {
        if (!array_key_exists($ceService, $this->ceResourceRegexMap)) {
            return [$resource, null];
        }

        $ret = preg_match($this->ceResourceRegexMap[$ceService], $resource, $matches);
        if ($ret === 0) {
            throw new \RuntimeException('Resource regex did not match');
        } elseif ($ret === false) {
            throw new \RuntimeException('Failed while matching resource regex');
        }

        return [$matches[1], $matches[2]];
    }
}
