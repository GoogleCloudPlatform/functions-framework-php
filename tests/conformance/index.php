<?php

use Google\CloudFunctions\CloudEvent;
use Psr\Http\Message\ServerRequestInterface;

define('OUTPUT_FILE', 'function_output.json');

function httpFunc(ServerRequestInterface $request)
{
    file_put_contents(OUTPUT_FILE, $request->getBody());
    return "ok" . PHP_EOL;
}

// PHP cannot distinguish between an empty array and an empty map because they
// are represented by the same type. This means that when a JSON object
// containing either an empty array or an empty map is decoded the type
// information is lost -- both will be an empty array. Furthermore, when an
// empty PHP array is encoded to JSON it will always be represented as '[]'.
// This means that a JSON -> PHP -> JSON round trip would look like this:
//
//  '{"foo": {}}' -> array('foo' => array()) -> '{"foo": []}'
//
// There is a way to get PHP to output '{}' when encoding JSON, though: the
// empty object. Unfortunately the built-in JSON decoder cannot use the empty
// object only for empty maps. It insists on using *only* arrays or *only*
// objects for *everything*, resulting in further problems when JSON arrays
// are forced into PHP objects. So this is not a solution to the round trip
// problem.
//
// The conformance tests depend on JSON -> [language representation] -> JSON
// transformations so the fact that PHP throws away type information is
// problematic. The function below addresses specific known cases of this
// problem in the conformance test suite by changing the PHP representation
// of the CloudEvent's data to use types that will encode correctly.
function fixCloudEventData(CloudEvent $cloudevent): CloudEvent
{
    $data = $cloudevent->getData();
    $dataModified = false;

    // These fields are known to be maps, so if they're present and empty
    // change them to the empty object so they encode as '{}'.
    $fields = ['oldValue', 'updateMask'];
    foreach ($fields as $f) {
        if (array_key_exists($f, $data) && empty($data[$f])) {
            $data[$f] = new stdClass();
            $dataModified = true;
        }
    }

    if ($dataModified) {
        // Clone the event but swap in the modified data.
        return new CloudEvent(
            $cloudevent->getId(),
            $cloudevent->getSource(),
            $cloudevent->getSpecversion(),
            $cloudevent->getType(),
            $cloudevent->getDatacontenttype(),
            $cloudevent->getDataschema(),
            $cloudevent->getSubject(),
            $cloudevent->getTime(),
            $data
        );
    }

    return $cloudevent;
}

function cloudEventFunc(CloudEvent $cloudevent)
{
    file_put_contents(OUTPUT_FILE, json_encode(fixCloudEventData($cloudevent)));
}
