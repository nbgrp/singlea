# Redis Bundle

## Overview

> Implements `nbgrp/singlea-persistence-contracts`.

Redis Bundle makes able to store clients metadata and their feature configs in a Redis instance with
help of the [SncRedisBundle](https://github.com/snc/SncRedisBundle). The bundle configuration allows
declaring certain features as required to prevent client registration without specify parameters for
these features.

## Installation

```
composer require nbgrp/singlea-redis-bundle
```

If you use Symfony Flex it enables the bundle automatically. Otherwise, to enable the bundle add the
following code:

``` php title="config/bundles.php"
return [
    // ...
    SingleA\Bundles\Redis\SingleaRedisBundle::class => ['all' => true],
];
```

## Configuration

The bundle configuration includes the following settings:

* `client_last_access_key` (default value "singlea:last-access") — the Redis hash table name which
  will be used to store last access timestamps for each client.
* `snc_redis_client` (default value "default") — the SncRedisBundle client alias name to be used to
  access the Redis instance.
  See [SncRedisBundle documentation](https://github.com/snc/SncRedisBundle/tree/master/docs#usage)
  for more details.
* `config_managers` (required) — a data structure which defines configuration for every feature
  config manager. Each feature config manager configuration present as a key-value pair where the
  key value has no specific meaning, but must be unique; the value should be a data structure with
  the following keys:
    * `key` (required) — the Redis hash table name which the config manager will use to store
      appropriate clients configs.
    * `config_marshaller` (required) — the feature config marshaller service id (see configuration
      example below).
    * `required` (boolean, default `false`) — defines is this feature mandatory for clients or not
      (during a registration).

1. Configure the SncRedisBundle and define client alias:

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

To add some custom feature in your SingleA instance, you must:

* create and implement your own feature config interface (which must
  extend `SingleA\Contracts\FeatureConfig\FeatureConfigInterface`), define new feature config
  marshaller in `services.yaml` with passing created interface FQCN as an argument to the marshaller
  factory;
* implement `SingleA\Contracts\FeatureConfig\FeatureConfigFactoryInterface` for processing
  registration data and creating client feature config instances.

Read [more details](../features/about.md#new-features) about creating new features.
