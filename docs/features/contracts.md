# SingleA Contracts

!!! info ""

    Inspired by the Symfony [Contracts](https://symfony.com/doc/current/components/contracts.html)
    component.

The SingleA Contracts is a set of interfaces, which make it possible to separate the abstract
service layer of the [SingleA Features](about.md) from the implementation. Thanks to the contracts
the SingleA features are easily interchangeable and extendable (through an ability to customize them
and create your own implementations).

Currently, the SingleA Contracts include the following (there may be more in the future):

* [FeatureConfig](https://github.com/nbgrp/singlea-feature-config-contracts) — basic interfaces for
  a SingleA Feature implementation.
* [Persistence](https://github.com/nbgrp/singlea-persistence-contracts) — interfaces of services
  that responsible for persisting of feature configs and metadata of clients.
* [Marshaller](https://github.com/nbgrp/singlea-marshaller-contracts) — interfaces of services for
  marshalling and encrypting of feature configs.
* [Tokenization](https://github.com/nbgrp/singlea-tokenization-contracts) — interfaces of the
  [Tokenization](tokenization.md) feature, which makes able to generate user tokens.
* [PayloadFetcher](https://github.com/nbgrp/singlea-payload-fetcher-contracts) — interfaces of the
  [Payload Fetcher](payload-fetcher.md) feature, which makes able to receive an additional payload
  data for the user token (during the token generation).
