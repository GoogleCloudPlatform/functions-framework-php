# Functions Framework for PHP [![Build Status](https://img.shields.io/endpoint.svg?url=https%3A%2F%2Factions-badge.atrox.dev%2FGoogleCloudPlatform%2Ffunctions-framework-php%2Fbadge&style=flat)](https://actions-badge.atrox.dev/GoogleCloudPlatform/functions-framework-php/goto) [![Packagist](https://poser.pugx.org/google/cloud-functions-framework/v/stable)](https://packagist.org/packages/google/cloud-functions-framework)

An open source FaaS (Function as a service) framework for writing portable
PHP functions.

The Functions Framework lets you write lightweight functions that run in many
different environments, including:

*   Your local development machine
*   [Cloud Run and Cloud Run on GKE](https://cloud.google.com/run/)
*   [Knative](https://github.com/knative/)-based environments

The framework allows you to go from:

```php
use Psr\Http\Message\ServerRequestInterface;

function helloHttp(ServerRequestInterface $request)
{
    return "Hello World from a PHP HTTP function!" . PHP_EOL;
}
```

To:

```sh
curl http://my-url
# Output: "Hello World from a PHP HTTP function!"
```

All without needing to worry about writing an HTTP server or complicated request
handling logic.

> Watch [this video](https://youtu.be/yMEcyAkTliU?t=912) to learn more about Functions Frameworks.

# Features

*   Spin up a local development server for quick testing
*   Invoke a function in response to a request
*   Automatically unmarshal events conforming to the [CloudEvents](https://cloudevents.io/) spec
*   Portable between serverless platforms

# Installation

Add the Functions Framework to your `composer.json` file using
[Composer][composer].

```sh
composer require google/cloud-functions-framework
```

[composer]: https://getcomposer.org/

# Define your Function

Create an `index.php` file with the following contents:

```php
<?php

use Psr\Http\Message\ServerRequestInterface;

function helloHttp(ServerRequestInterface $request)
{
    return "Hello World from a PHP HTTP function!" . PHP_EOL;
}
```

# Quickstarts

## Run your function locally

After completing the steps under **Installation** and **Define your Function**,
run the following commands:

```sh
export FUNCTION_TARGET=helloHttp
php -S localhost:8080 vendor/bin/router.php
```

Open `http://localhost:8080/` in your browser and see *Hello World from a PHP HTTP function!*.


## Run your function in a container

After completing the steps under **Installation** and **Define your Function**, build the container using the example `Dockerfile`:

```
docker build . \
    -f vendor/google/cloud-functions-framework/examples/hello/Dockerfile \
    -t my-cloud-function
```

Run the cloud functions framework container:

```
docker run -p 8080:8080 \
    -e FUNCTION_TARGET=helloHttp \
    my-cloud-function
```

Open `http://localhost:8080/` in your browser and see *Hello World from a PHP
HTTP function*. You can also send requests to this function using `curl` from
another terminal window:

```sh
curl localhost:8080
# Output: Hello World from a PHP HTTP function!
```

## Run your function on Google Cloud Functions

**NOTE**: For an extensive list of samples, see the [PHP functions samples][functions-samples]
and the [official how-to guides][functions-how-to].

Follow the steps below to deploy to Google Cloud Functions. More information
on function deployment is available in the
[GCF documentation](https://cloud.google.com/functions/docs/deploying).

To run your function on Cloud Functions, first you must have the [gcloud SDK][gcloud] installed and [authenticated][gcloud-auth].

Make sure your source file (which defines your function) is called
`index.php`. The Functions Framework lets you choose a function source file,
but Cloud Functions currently uses the default of `index.php`.

Decide _which_ function in the source file to invoke, that is, the name that you
used when writing the function. This is called the **target**.

Choose a Cloud Functions **name** for your function. The **name** identifies
this function deployment (e.g. in the cloud console) and is also part of the
function's default URL. (Note: the **name** and the **target** do not have to
be the same value.)

Then, from the directory containing your function source, issue the gcloud command to deploy:

```sh
gcloud functions deploy $YOUR_FUNCTION_NAME \
    --runtime=php74 \
    --entry-point=$YOUR_FUNCTION_TARGET \
    --trigger-http
```

The `--entry-point` flag can be omitted if the **target** has the same value
as the **name**.

If your function handles events rather than HTTP requests, you'll need to
replace `--trigger-http` with a different trigger. For details, see the
[reference documentation](https://cloud.google.com/sdk/gcloud/reference/functions/deploy)
for `gcloud functions deploy`.

To update your deployment, just redeploy using the same function **name**.
Configuration flags are not required.

## Run your function in Cloud Run

To run your function in Cloud Run, first you must have the [gcloud SDK][gcloud] installed and [authenticated][gcloud-auth].

Additionally, you need to have a Google Cloud project ID for the
[Google Cloud Project][gcp-project] you want to use.

After completing the steps under **Installation** and **Define your Function**, build the container using the example `Dockerfile`. This Dockerfile is
built on top of the [App Engine runtime for PHP 7.4][gae-php7], but you can use
any container you want as long as your application listens on **Port 8080**.

```sh
docker build . \
    -f vendor/google/cloud-functions-framework/examples/hello/Dockerfile \
    -t gcr.io/$GCLOUD_PROJECT/my-cloud-function
```

> **NOTE**: Be sure to replace `$GCLOUD_PROJECT` with your Google Cloud project
ID, or set the environment variable using `export GCLOUD_PROJECT="some-project-id"`.

Next, push your image to [Google Container Registry](https://cloud.google.com/container-registry). This will allow you to deploy it directly from Cloud Run.

```sh
docker push gcr.io/$GCLOUD_PROJECT/my-cloud-function
```

Finally, use the `gcloud` command-line tool to deploy to Cloud Run:

```sh
gcloud run deploy my-cloud-function \
    --image=gcr.io/$GCLOUD_PROJECT/my-cloud-function \
    --platform managed \
    --set-env-vars "FUNCTION_TARGET=helloHttp" \
    --allow-unauthenticated \
    --region $CLOUD_RUN_REGION \
    --project $GCLOUD_PROJECT
```

> **NOTE**: Be sure to replace `$CLOUD_RUN_REGION` with the
[correct region][cloud-run-regions] for your Cloud Run instance, for example
`us-central1`.

After your instance deploys, you can access it at the URL provided, or view it
in the [Cloud Console][cloud-run-console].

[functions-samples]: https://github.com/GoogleCloudPlatform/php-docs-samples/tree/master/functions
[functions-how-to]: https://cloud.google.com/functions/docs/how-to
[gcloud]: https://cloud.google.com/sdk/gcloud/
[gcloud-auth]: https://cloud.google.com/sdk/docs/authorizing
[gcp-project]: https://cloud.google.com/resource-manager/docs/creating-managing-projects
[gae-php7]: https://cloud.google.com/appengine/docs/standard/php7/runtime
[cloud-run-regions]: https://cloud.google.com/run/docs/locations
[cloud-run-console]: https://console.cloud.google.com/run

## Use CloudEvents

The Functions Framework can unmarshall incoming [CloudEvents][cloud-events]
payloads to a `cloudevent` object. This will be passed as arguments to your
function when it receives a request. Note that your function must use the
cloudevent function signature:

```php
use Google\CloudFunctions\CloudEvent;

function helloCloudEvents(CloudEvent $cloudevent)
{
    // Print the whole CloudEvent
    $stdout = fopen('php://stdout', 'wb');
    fwrite($stdout, $cloudevent);
}
```

You will also need to set the `FUNCTION_SIGNATURE_TYPE` environment
variable to `cloudevent`.

```sh
export FUNCTION_TARGET=helloCloudEvent
export FUNCTION_SIGNATURE_TYPE=cloudevent
php -S localhost:8080 vendor/bin/router.php
```

In a separate tab, make a cURL request in Cloud Event format to your function:

```
curl localhost:8080 \
    -H "ce-id: 1234567890" \
    -H "ce-source: //pubsub.googleapis.com/projects/MY-PROJECT/topics/MY-TOPIC" \
    -H "ce-specversion: 1.0" \
    -H "ce-type: com.google.cloud.pubsub.topic.publish" \
    -d '{"foo": "bar"}'
```

Your original process should output the following:

```
CLOUDEVENT metadata:
- id: 1234567890
- source: //pubsub.googleapis.com/projects/MY-PROJECT/topics/MY-TOPIC
- specversion: 1.0
- type: com.google.cloud.pubsub.topic.publish
- datacontenttype:
- dataschema:
- subject:
- time:
```

**IMPORTANT**: The above tutorials to deploy to a docker container and to
Cloud Run work for CloudEvents as well, as long as `FUNCTION_TARGET` and
`FUNCTION_SIGNATURE_TYPE` are set appropriately.

[cloud-events]: http://cloudevents.io

## Working with PSR-7 HTTP Objects

The first parameter of your function is a `Request` object which implements the
PSR-7 `ServerRequestInterface`:

```php
use Psr\Http\Message\ServerRequestInterface;

function helloHttp(ServerRequestInterface $request): string
{
    return sprintf("Hello %s from PHP HTTP function!" . PHP_EOL,
        $request->getQueryParams()['name'] ?? 'World');
}
```

You can return a PSR-7 compatible `ResponseInterface` instead of a string. This
allows you to set additional request properties such as the HTTP Status Code
and headers.

```php
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Utils;

function helloHttp(ServerRequestInterface $request): ResponseInterface
{
    $body = sprintf("Hello %s from PHP HTTP function!" . PHP_EOL,
        $request->getQueryParams()['name'] ?? 'World');

    return (new Response())
        ->withBody(Utils::streamFor($body))
        ->withStatus(418) // I'm a teapot
        ->withHeader('Foo', 'Bar');
}
```

A request to this function will produce a response similar to the following:

```
HTTP/1.1 418 I'm a teapot
Host: localhost:8080
Date: Wed, 03 Jun 2020 00:48:38 GMT
Foo: Bar

Hello World from PHP HTTP function!
```

See the [PSR-7 documentation][psr7] documentation for more on working
with the request and response objects.

[psr7]: https://www.php-fig.org/psr/psr-7/

## Use Google Cloud Storage

When you require the `google/cloud-storage` package with composer, the functions
framework will register the `gs://` stream wrapper. This enables your function
to read and write to Google Cloud Storage as you would any filesystem:

```php
// Get the contents of an object in GCS
$object = file_get_contents('gs://{YOUR_BUCKET_NAME}/object.txt');
// Make modifications
$object .= "\nadd a line";
// Write the new contents back to GCS
file_put_contents('gs://{YOUR_BUCKET_NAME}/object.txt', $object);
```

You can unregister this at any time by using
[`stream_wrapper_unregister`][stream_wrapper_unregister]:

```php
// unregister the automatically registered one
stream_wrapper_unregister('gs');
```

[stream_wrapper_unregister]: https://www.php.net/manual/en/function.stream-wrapper-unregister.php

## Run your function on Knative

Cloud Run and Cloud Run on GKE both implement the
[Knative Serving API](https://www.knative.dev/docs/). The Functions Framework is
designed to be compatible with Knative environments. Just build and deploy your
container to a Knative environment.

If you want even more control over the environment, you can
[deploy your container image to Cloud Run on GKE](https://cloud.google.com/run/docs/quickstarts/prebuilt-deploy-gke).
With Cloud Run on GKE, you can run your function on a GKE cluster, which gives
you additional control over the environment (including use of GPU-based
instances, longer timeouts and more).

# Configure the Functions Framework

You can configure the Functions Framework using the environment variables shown below:

| Environment variable      | Description
| ------------------------- | -----------
| `FUNCTION_TARGET`         | The name of the exported function to be invoked in response to requests.
| `FUNCTION_SOURCE` | The name of the file containing the source code for your function to load. Default: **`index.php`** (if it exists)
| `FUNCTION_SIGNATURE_TYPE` | The signature used when writing your function. Controls unmarshalling rules and determines which arguments are used to invoke your function. Can be either `http`, `event`, or `cloudevent`. Default: **`http`**

# Contributing

Contributions to this library are welcome and encouraged. See
[CONTRIBUTING](CONTRIBUTING.md) for more information on how to get started.
