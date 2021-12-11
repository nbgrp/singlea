# SingleA JSON Payload Fetcher Bundle

[![Latest Stable Version](http://poser.pugx.org/nbgrp/singlea-json-fetcher-bundle/v)](https://packagist.org/packages/nbgrp/singlea-json-fetcher-bundle)
[![Latest Unstable Version](http://poser.pugx.org/nbgrp/singlea-json-fetcher-bundle/v/unstable)](https://packagist.org/packages/nbgrp/singlea-json-fetcher-bundle)
[![Total Downloads](http://poser.pugx.org/nbgrp/singlea-json-fetcher-bundle/downloads)](https://packagist.org/packages/nbgrp/singlea-json-fetcher-bundle)
[![License](http://poser.pugx.org/nbgrp/singlea-json-fetcher-bundle/license)](https://packagist.org/packages/nbgrp/singlea-json-fetcher-bundle)

[![ko-fi](https://ko-fi.com/img/githubbutton_sm.svg)](https://ko-fi.com/S6S073WSW)

## Overview

This bundle is a part of the [SingleA project](https://github.com/nbgrp/singlea). It implements
receiving an additional user token payload from an external endpoint via an HTTP request containing
JSON with an array composed of user attributes.

## Installation

```
composer require nbgrp/singlea-json-fetcher-bundle
```

If you use Symfony Flex it enables the bundle automatically. Otherwise, to enable the bundle add the
following code in `config/bundles.php`:

``` php
return [
    // ...
    SingleA\Bundles\JsonFetcher\SingleaJsonFetcherBundle::class => ['all' => true],
];
```

## Configuration

By default, requests to http (unsecure) endpoints are denied. To permit them, you need to add the
following bundle configuration in `config/packages/singlea_json_fetcher.yaml`:

``` yaml
singlea_json_fetcher:
    https_only: false
```

The bundle uses default Symfony HttpClient. If you need to pass scoped HttpClient, you should
override the `SingleA\Bundles\JsonFetcher\JsonFetcher` service constructor argument:

``` yaml
services:
    # ...
    SingleA\Bundles\JsonFetcher\JsonFetcher:
        arguments: [ '@custom.client' ]

```
