# JWT Bundle

## Overview

> Implements `nbgrp/singlea-tokenization-contracts`.

The JWT bundle makes able to generate user token as
a [JWT](https://datatracker.ietf.org/doc/html/rfc7519). Due to the Spomky's
[JWT Framework](https://github.com/web-token/jwt-framework/), in addition
to mandatory signature, the generated JWT can be also encrypted.

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
composer require nbgrp/singlea-jwt-bundle
```

### Enable the Bundle

If you use Symfony Flex, it enables the bundle automatically. Otherwise, to enable the bundle add
the following code:

``` php title="config/bundles.php"
return [
    // ...
    SingleA\Bundles\Jwt\SingleaJwtBundle::class => ['all' => true],
];
```

## Configuration

It is possible to configure a default JWT lifetime value for new clients who did not specify
`token.ttl` registration parameter explicitly. For this purpose use
the `singlea_jwt.config_default_ttl` parameter.

With `singlea_jwt.issuer` it is possible to configure
the [`iss` JWT claim](https://datatracker.ietf.org/doc/html/rfc7519#section-4.1.1) value.

``` yaml title="config/packages/singlea_jwt.yaml"
singlea_jwt:
    config_default_ttl: 1200
    issuer: 'SingleA'
```

## Client Registration

### Request Parameters

The JWT bundle uses `token` as the name (key) and `jwt` as the hash value to determine own
parameters in client registration request. Besides the hash value, the bundle has the following
registration request parameters:

* `ttl` (optional) — a JWT lifetime in seconds.
* `claims` (optional) — a list of user attribute names to be included in the JWT payload (if they
  exist for the user). If a claim ends with `[]` (square braces), user attribute named without
  braces will be included in the JWT payload as an array; if the attribute was not an array, it will
  be present as a one-element list. If claim does not end with `[]`, the JWT payload will contain a
  scalar value; if user attribute is an array, only the first element will be included.
* `jws` (required) — JWT signature parameters:
    * `alg` (required) — a signature algorithm which should be used for a JWT sending to the
      endpoint according [RFC 7518](https://www.rfc-editor.org/rfc/rfc7518.html#section-3.1) (except
      "none").
    * `bits` (optional) — a number of bits for generating an OpenSSL private key (applicable for
      RSA and octet algorithms).
* `jwe` (optional) — JWT encryption parameters:
    * `alg` (required) — a key encryption algorithm according RFC 7518:
      [4. Cryptographic Algorithms for Key Management](https://www.rfc-editor.org/rfc/rfc7518.html#section-4).
    * `enc` (required) — a content encryption algorithm according RFC 7518:
      [5. Cryptographic Algorithms for Content Encryption](https://www.rfc-editor.org/rfc/rfc7518.html#section-5).
    * `zip` (optional) — a JWE compression algorithm according RFC 7518:
      [7.3. JSON Web Encryption Compression Algorithms Registry](https://www.rfc-editor.org/rfc/rfc7518.html#section-7.3).
    * `jwk` (required) — a JSON data structure which represent a recipient public JWK
      according RFC 7518:
      [6. Cryptographic Algorithms for Keys](https://www.rfc-editor.org/rfc/rfc7518.html#section-6).

!!! tip

    You can use a helpful CLI tool from Spomky's JWT Framework for
    [JWK generation](https://web-token.spomky-labs.com/console-command/console#key-generators).

``` yaml
{
    # ...
    "token": {
        "#": "jwt",
        "ttl": 600,
        "claims": [
            "username",
            "email",
            "role[]"
        ],
        "jws": {
            "alg": "RS256",
            "bits": 512
        },
        "jwe": {
            "alg": "RSA-OAEP-256",
            "enc": "A256GCM",
            "zip": "DEF",
            "jwk": { ... }
        }
    }
}
```

### Output

The JWT bundle adds to the client registration output the public JWK, which can be used further to
verify the signature of the user's JWT.

``` yaml
{
    # ...
    "token": {
        "jwk": { ... }
    }
}
```
