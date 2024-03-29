# About SingleA Features

One of the key features of the SingleA project is a modular approach for code organization. Modules
in SingleA have a special name — **Features**. This is a reference to the feature oriented
architecture. The SingleA Features based on the following principles.

* Each feature has a **key** (name) and a **hash** (implementation). The registration request data
  must contain the name as a root key for the feature configuration, and the implementation as a "#"
  item. The "#" item may be omitted if the feature has only one implementation (in this case the key
  and hash must be equal).

!!! example ""

    ``` yaml
    {
        "some-feature-name": {
            "#": "some-implementation",
            "option": "value",
            # ...
        }
    }
    ```

* Each client can enable only one feature (as a result of registration), but different clients can
  enable different implementations of the same feature. For example, clients can
  enable [JSON Fetcher](../bundles/json-fetcher.md)
  **or** [JWT Fetcher](../bundles/jwt-fetcher.md) as an implementation of
  the [Payload Fetcher](payload-fetcher.md) feature.

* Each feature has a config that must have an interface, which must extend
  the `SingleA\Contracts\FeatureConfig\FeatureConfigInterface`. Also, for creating an instance of
  the feature config from client registration data, it is necessary
  the `SingleA\Contracts\FeatureConfig\FeatureConfigFactoryInterface` to be implemented.

Using the config and config factory interfaces you can
both [customize](../bundles/singlea.md#customization) behavior of existing features implementations,
and create your own implementations.

It is worth to be noted that the interfaces `SingleA\Contracts\FeatureConfig\FeatureConfigInterface`
and `SingleA\Contracts\FeatureConfig\FeatureConfigFactoryInterface` are the part of
the [SingleA Contracts](contracts.md), which is another one concept underlying the SingleA project.

The SingleA project includes implementations of the [Tokenization](tokenization.md)
and [Payload Fetcher](payload-fetcher.md) features (as separate bundles), and
the [Request Signature](signature.md) feature (as a part of
the [SingleA bundle](../bundles/singlea.md)).
