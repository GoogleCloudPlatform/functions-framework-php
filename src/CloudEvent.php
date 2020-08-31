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

class CloudEvent
{
    // Required Fields
    private $id;
    private $source;
    private $specversion;
    private $type;

    // Optional Fields
    private $datacontenttype;
    private $dataschema;
    private $subject;
    private $time;
    private $data;

    public function __construct(
        string $id,
        string $source,
        string $specversion,
        string $type,
        ?string $datacontenttype,
        ?string $dataschema,
        ?string $subject,
        ?string $time,
        ?array $data
    ) {
        $this->id = $id;
        $this->source = $source;
        $this->specversion = $specversion;
        $this->type = $type;
        $this->datacontenttype = $datacontenttype;
        $this->dataschema = $dataschema;
        $this->subject = $subject;
        $this->time = $time;
        $this->data = $data;
    }

    public function setId(string $id)
    {
        $this->id = $id;
    }
    public function getId()
    {
        return $this->id;
    }
    public function setSource(string $source)
    {
        $this->source = $source;
    }
    public function getSource()
    {
        return $this->source;
    }
    public function setSpecVersion(string $specversion)
    {
        $this->specversion = $specversion;
    }
    public function getSpecVersion()
    {
        return $this->specversion;
    }
    public function setType(string $type)
    {
        $this->type = $type;
    }
    public function getType()
    {
        return $this->type;
    }
    public function setDataContentType(string $datacontenttype)
    {
        $this->datacontenttype = $datacontenttype;
    }
    public function getDataContentType()
    {
        return $this->datacontenttype;
    }
    public function setDataSchema(string $dataschema)
    {
        $this->dataschema = $dataschema;
    }
    public function getDataSchema()
    {
        return $this->dataschema;
    }
    public function setSubject(string $subject)
    {
        $this->subject = $subject;
    }
    public function getSubject()
    {
        return $this->subject;
    }
    public function setTime(string $time)
    {
        $this->time = $time;
    }
    public function getTime()
    {
        return $this->time;
    }
    public function setData(string $data)
    {
        $this->data = $data;
    }
    public function getData()
    {
        return $this->data;
    }

    public static function fromArray(array $arr)
    {
        $argKeys = ['id', 'source', 'specversion', 'type', 'datacontenttype', 'dataschema', 'subject', 'time', 'data'];
        $args = [];
        foreach ($argKeys as $key) {
            $args[] = $arr[$key] ?? null;
        }

        return new static(...$args);
    }

    public function __toString()
    {
        $data_as_json = json_encode($this->data);
        $output = implode("\n", [
            'CLOUDEVENT:',
            "- id: $this->id",
            "- source: $this->source",
            "- specversion: $this->specversion",
            "- type: $this->type",
            "- datacontenttype: $this->datacontenttype",
            "- dataschema: $this->dataschema",
            "- subject: $this->subject",
            "- time: $this->time",
            "- data: $data_as_json",
        ]);
        return $output;
    }
}
