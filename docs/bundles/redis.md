# Redis Bundle

## Overview

> Implements `nbgrp/singlea-persistence-contracts`.

The Redis bundle makes able to store clients metadata and their feature configs in a Redis instance
using the [SncRedisBundle](https://github.com/snc/SncRedisBundle). The bundle configuration allows
declaring certain [SingleA features](../features/about.md) as required to prevent client
registration without specify parameters for these features.

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
composer require nbgrp/singlea-redis-bundle
```

### Enable the Bundle

If you use Symfony Flex, it enables the bundle automatically. Otherwise, to enable the bundle add
the following code:

``` php title="config/bundles.php"
return [
    // ...
    SingleA\Bundles\Redis\SingleaRedisBundle::class => ['all' => true],
];
```

## Configuration

The bundle configuration includes the following settings:

* `client_last_access_key` (default value "singlea:last-access") — the Redis hash table name, which
  will be used to store last access timestamps for each client.
* `snc_redis_client` (default value "default") — the SncRedisBundle client alias name to be used to
  access the Redis instance.
  See [SncRedisBundle documentation](https://github.com/snc/SncRedisBundle/tree/master/docs#usage)
  for more details.
* `config_managers` (required) — a data structure which defines the configuration for every feature
  config manager. The configuration of each feature config manager is presented as a key-value pair
  where the key value has no specific meaning, but must be unique; the value should be a data
  structure with the following keys:
    * `key` (required) — the name of the Redis hash table that the config manager will use to store
      appropriate clients configs.
    * `config_marshaller` (required) — the feature config marshaller service id (see configuration
      example below).
    * `required` (boolean, default `false`) — defines is this feature mandatory for clients or not
      (at registration).

1. Configure the SncRedisBundle and define the client alias:

``` yaml title="config/packages/snc_redis.yaml"
snc_redis:
    clients:
        default:
            type: phpredis
            alias: default
            dsn: "%env(REDIS_URL)%"
            logging: false
```

2. Configure feature config marshallers in the DI container:

``` yaml title="config/services.yaml"
services:
    # ...
    singlea.signature_marshaller:
        class: 'SingleA\Contracts\Marshaller\FeatureConfigMarshallerInterface'
        factory: '@SingleA\Bundles\Singlea\Service\Marshaller\FeatureConfigMarshallerFactory'
        arguments: [ 'SingleA\Bundles\Singlea\FeatureConfig\Signature\SignatureConfigInterface' ]

    singlea.tokenizer_marshaller:
        class: 'SingleA\Contracts\Marshaller\FeatureConfigMarshallerInterface'
        factory: '@SingleA\Bundles\Singlea\Service\Marshaller\FeatureConfigMarshallerFactory'
        arguments: [ 'SingleA\Contracts\Tokenization\TokenizerConfigInterface' ]

    singlea.payload_fetcher_marshaller:
        class: 'SingleA\Contracts\Marshaller\FeatureConfigMarshallerInterface'
        factory: '@SingleA\Bundles\Singlea\Service\Marshaller\FeatureConfigMarshallerFactory'
        arguments: [ 'SingleA\Contracts\PayloadFetcher\FetcherConfigInterface' ]
```

3. Finally, configure the SingleA Redis Bundle:

``` yaml title="config/packages/singlea_redis.yaml"
singlea_redis:
    client_last_access_key: 'singlea:last-access'
    snc_redis_client: 'default'
    config_managers:
        signature:
            key: 'singlea:signature'
            config_marshaller: 'singlea.signature_marshaller'
            required: true

        tokenizer:
            key: 'singlea:token'
            config_marshaller: 'singlea.tokenizer_marshaller'
            required: true

        payload_fetcher:
            key: 'singlea:payload'
            config_marshaller: 'singlea.payload_fetcher_marshaller'
```

## Add new feature

To add some custom feature in your SingleA instance, you should:

* create and implement your own feature config interface (which must
  extend `SingleA\Contracts\FeatureConfig\FeatureConfigInterface`), define a new feature config
  marshaller in `services.yaml` with passing created interface FQCN as an argument to the marshaller
  factory;
* implement `SingleA\Contracts\FeatureConfig\FeatureConfigFactoryInterface` for processing
  registration data and creating client feature config instances.

Read more details about [SingleA Features](../features/about.md)
and [SingleA Contracts](../features/contracts.md).
