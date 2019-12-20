**DISCLAIMER: This repository is in development and not meant for production use**

# Functions Framework for PHP [![Build Status](https://travis-ci.com/GoogleCloudPlatform/functions-framework-php.svg?branch=master)](https://travis-ci.com/GoogleCloudPlatform/functions-framework-php) [![Packagist](https://poser.pugx.org/google/cloud-functions-framework/v/stable)](https://packagist.org/packages/google/cloud-functions-framework)

An open source FaaS (Function as a service) framework for writing portable
PHP functions.

The Functions Framework lets you write lightweight functions that run in many
different environments, including:

*   Your local development machine
*   [Cloud Run and Cloud Run on GKE](https://cloud.google.com/run/)
*   [Knative](https://github.com/knative/)-based environments

The framework allows you to go from:

```php
function helloHttp()
{
    return "Hello World from PHP HTTP function!" . PHP_EOL;
}
```

To:

```sh
curl http://my-url
# Output: "Hello World from PHP HTTP function!"
```

All without needing to worry about writing an HTTP server or complicated request
handling logic.

> Watch [this video](https://youtu.be/yMEcyAkTliU?t=912) to learn more about Functions Frameworks.

# Features

*   Spin up a local development server for quick testing
*   Invoke a function in response to a request
*   Portable between serverless platforms

# Installation

Add the Functions Framework to your `composer.json` file using `composer`.

```sh
composer require google/cloud-functions-framework
```

# Run your function locally on your local machine

Create an `index.php` file with the following contents:

```php
<?php

use Symfony\Component\HttpFoundation\Request;

function helloHttp(Request $request)
{
    return "Hello World from PHP HTTP function!" . PHP_EOL;
}
```

Run the following commands:

```sh
export FUNCTION_TARGET=helloHttp
export FUNCTION_SIGNATURE_TYPE=http
export FUNCTION_SOURCE=index.php
php -S localhost:8080 vendor/bin/router.php
```

Open `http://localhost:8080/` in your browser and see *Hello World...*.


# Run your function in a container

Create an `index.php` file with the following contents:

```php
<?php

use Symfony\Component\HttpFoundation\Request;

function helloHttp(Request $request)
{
    return "Hello World from PHP HTTP function!" . PHP_EOL;
}
```

Now install the Functions Framework:

```sh
composer install google-cloud/functions-framework
```

Build the container using the example Dockerfile:

```
docker build . \
    -f vendor/google/cloud-functions/framework/examples/hello/Dockerfile \
    -t my-cloud-function
```

Run the cloud functions framework container:

```
docker run -p 8080:8080 \
    -e FUNCTION_TARGET=helloHttp \
    -e FUNCTION_SIGNATURE_TYPE=http \
    -e FUNCTION_SOURCE=index.php \
    my-cloud-function
```

Open `http://localhost:8080/` in your browser and see *Hello World...*, or
send requests to this function using `curl` from another terminal window:

```sh
curl localhost:8080
# Output: Hello World from PHP HTTP function!
```

## Accessing the HTTP Object

The first parameter of your function is a `Request` object from `symfony/http-foundation`:

```php
use Symfony\Component\HttpFoundation\Request;

function helloHttp(Request $request)
{
    return sprintf("Hello %s from PHP HTTP function!" . PHP_EOL,
        $request->query->get('name') ?: 'World'
    );
}
```

See the [HttpFoundation documentation][httpfoundation] documentation for more on working
with the request object.

[httpfoundation]: https://symfony.com/doc/current/components/http_foundation.html

# Run your function on serverless platforms

[Google Cloud Functions](https://cloud.google.com/functions/docs) does _not_ have native support for PHP. Furthermore, it _can not_ use custom Function Frameworks.

To get around these restrictions, we can use a Knative-based hosting platform such as [Cloud Run](https://cloud.google.com/run/docs) or Cloud Run on GKE](https://cloud.google.com/run/docs/gke/setup).

## Cloud Run/Cloud Run on GKE

Once you've written your function and added the Functions Framework to `composer.json`, all that's left is to create a container image. [Check out the Cloud Run quickstart](https://cloud.google.com/run/docs/quickstarts/build-and-deploy) for PHP to create a container image and deploy it to Cloud Run. You'll write a `Dockerfile` when you build your container. This `Dockerfile` allows you to specify exactly what goes into your container (including custom binaries, a specific operating system, and more).

If you want even more control over the environment, you can [deploy your container image to Cloud Run on GKE](https://cloud.google.com/run/docs/quickstarts/prebuilt-deploy-gke). With Cloud Run on GKE, you can run your function on a GKE cluster, which gives you additional control over the environment (including use of GPU-based instances, longer timeouts and more).

## Container environments based on Knative

Cloud Run and Cloud Run on GKE both implement the [Knative Serving API](https://www.knative.dev/docs/). The Functions Framework is designed to be compatible with Knative environments. Just build and deploy your container to a Knative environment.

# Configure the Functions Framework

You can configure the Functions Framework using the environment variables shown below:

| Environment variable      | Description
| ------------------------- | -----------
| `FUNCTION_TARGET`         | The name of the exported function to be invoked in response to requests.
| `FUNCTION_SIGNATURE_TYPE` | The signature used when writing your function. Controls unmarshalling rules and determines which arguments are used to invoke your function. Can be either `http` or `event`.

# Enable CloudEvents

The Functions Framework can unmarshall incoming
[CloudEvents](http://cloudevents.io) payloads to `data` and `context` objects.
These will be passed as arguments to your function when it receives a request.
Note that your function must use the event-style function signature:

```php
function helloEvents($data, $context)
{
  var_dump($data);
  var_dump($context);
}
```

To enable automatic unmarshalling, set the `FUNCTION_SIGNATURE_TYPE` environment
variable to `event`. For more details on this signature type, check out the Google Cloud Functions
documentation on
[background functions](https://cloud.google.com/functions/docs/writing/background#cloud_pubsub_example).

# Contributing

Contributions to this library are welcome and encouraged. See
[CONTRIBUTING](CONTRIBUTING.md) for more information on how to get started.
