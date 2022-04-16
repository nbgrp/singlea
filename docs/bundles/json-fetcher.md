# JSON Fetcher Bundle

## Overview

> Implements the `nbgrp/singlea-payload-fetcher-contracts`.

The JSON Fetcher bundle implements receiving an additional user token payload from an external
endpoint via an HTTP request containing a JSON with an array composed of user attributes.

## Installation

### Symfony Flex

If you use Symfony Flex, you can add an endpoint for access nb:group's recipes, which makes it
possible to apply the default bundle configuration automatically when install the bundle:

```
composer config --json extra.symfony.endpoint '["https://api.github.com/repos/nbgrp/recipes/contents/index.json", "flex://defaults"]'
```

If you wish (or you already have some value of the `extra.symfony.endpoint` option), you can do the
same by updating your `composer.json` directly:

``` json title="composer.json"
{
    "name": "acme/singlea",
    "description": "ACME SingleA",
    "extra": {
        "symfony": {
            "endpoint": [
                "https://api.github.com/repos/nbgrp/recipes/contents/index.json",
                "flex://defaults"
            ]
        }
    }
}
```

Then you can install the bundle using Composer:

```
composer require nbgrp/singlea-json-fetcher-bundle
```

### Enable the Bundle

If you use Symfony Flex, it enables the bundle automatically. Otherwise, to enable the bundle add
the following code:

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

## Client Registration

### Request Parameters

The JSON Fetcher bundle uses `payload` as the name (key) and `json` as the hash value to determine
own parameters in the client registration data. Besides the hash value, the bundle has the following
registration request parameters:

* `endpoint` (required) — a URL to which the POST request should be sent, that contains an array of
  user attributes according the `claims` parameter (it may be an empty array).
* `claims` (optional) — an array of the names of user attributes, which should be included in the
  request data (if they exist for the user). If a claim ends with `[]` (square braces), user
  attribute named without braces will be included in request data as an array; if the attribute was
  not an array, it will be present as a one-element list. If claim does not end with `[]`, request
  data will contain a scalar value; if user attribute is an array, only the first element will be
  included.
* `options` (optional) — a JSON data structure with additional options which will be passed into the
  method `Symfony\Contracts\HttpClient\HttpClientInterface::request`.

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

The JSON Fetcher bundle does not add any data to the client registration output.
