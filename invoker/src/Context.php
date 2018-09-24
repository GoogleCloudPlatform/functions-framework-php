<?php

namespace Google\CloudFunctions;

class Context
{
    public $eventId;
    public $timestamp;
    public $eventType;
    public $resource;

    public function __construct($eventId, $timestamp, $eventType, $resource)
    {
        $this->eventId = $eventId;
        $this->timestamp = $timestamp;
        $this->eventType = $eventType;
        $this->resource = $resource;
    }

    public static function fromArray(array $arr)
    {
        $argKeys = ['eventId', 'timestamp', 'eventType', 'resource'];
        $args = [];
        foreach ($argKeys as $key) {
            $args[] = $arr[$key];
        }

        return new static(...$args);
    }
}
