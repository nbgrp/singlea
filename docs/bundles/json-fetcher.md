# JSON Fetcher Bundle

## Overview

> Implements the `nbgrp/singlea-payload-fetcher-contracts`.

JSON Fetcher Bundle implements receiving an additional user token payload from an external endpoint
via an HTTP request containing JSON with an array composed of user attributes.

## Installation

```
composer require nbgrp/singlea-json-fetcher-bundle
```

If you use Symfony Flex it enables the bundle automatically. Otherwise, to enable the bundle add the
following code:

``` php title="config/bundles.php"
return [
    // ...
    SingleA\Bundles\JsonFetcher\SingleaJsonFetcherBundle::class => ['all' => true],
];
```

## Configuration

Requests to http (unsecure) endpoints are denied by default. To permit them, you need to add the
following bundle configuration:

``` yaml title="config/packages/singlea_json_fetcher.yaml"
singlea_json_fetcher:
    https_only: false
```

The bundle uses default [Symfony HttpClient](https://symfony.com/doc/current/http_client.html). If
you need to pass [scoped](https://symfony.com/doc/current/http_client.html#scoping-client)
HttpClient, you should override the service `SingleA\Bundles\JsonFetcher\JsonFetcher` constructor
argument:

``` yaml title="services.yaml"
services:
    # ...
    SingleA\Bundles\JsonFetcher\JsonFetcher:
        arguments: [ '@custom.client' ]

```

## Client registration

### Request parameters

JSON Fetcher Bundle use `json` as hash value to determine own parameters in client registration request.
Besides the hash value, it has parameters:

* `endpoint` (required) — a URL where must be sent a POST request which contains an array of user
  attributes according `claims` parameter (it may be an empty array).
* `claims` (optional) — an array of user attributes names which should be included in the request
  data (if they exist for the user). If a claim ends with `[]` (square braces), user attribute named
  without braces will be included in request data as an array; if the attribute was not an array, it
  will be present as a one-element array. If claim does not end with `[]`, request data will contain
  a scalar value; if user attribute is an array, only the first element will be included.
* `options` (optional) — a JSON data structure with additional options which will be passed
  into the method `Symfony\Contracts\HttpClient\HttpClientInterface::request`.

Example:

``` yaml
{
    # ...
    "payload": {
        "#": "json",
        "endpoint": "https://example.domain/_singlea_payload",
        "claims": [
            "email",
            "role[]"
        ],
        "options": {
            "timeout": 5
        }
    }
}
```

### Output

JSON Fetcher Bundle does not add any data to the client registration output.
