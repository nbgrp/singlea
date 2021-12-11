# JWT Bundle

## Overview

> Implements `nbgrp/singlea-tokenization-contracts`.

JWT Bundle makes able to generate user token as
a [JWT](https://datatracker.ietf.org/doc/html/rfc7519). Due to Spomky's
[JWT Framework](https://github.com/web-token/jwt-framework/), in addition
to mandatory signature, generated JWT can be also encrypted.

## Installation

```
composer require nbgrp/singlea-jwt-bundle
```

If you use Symfony Flex it enables the bundle automatically. Otherwise, to enable the bundle add the
following code:

``` php title="config/bundles.php"
return [
    // ...
    SingleA\Bundles\Jwt\SingleaJwtBundle::class => ['all' => true],
];
```

## Configuration

It is possible to configure a default JWT lifetime value for new clients who do not specified
`token.ttl` registration parameter explicitly. For this purpose use
the `singlea_jwt.config_default_ttl` parameter.

With `singlea_jwt.issuer` it is possible to configure
the [`iss` JWT claim](https://datatracker.ietf.org/doc/html/rfc7519#section-4.1.1) value.

``` yaml title="config/packages/singlea_jwt.yaml"
singlea_jwt:
    config_default_ttl: 1200
    issuer: 'SingleA'
```

## Client registration

JWT Bundle use `jwt` as hash value to determine own parameters in client registration request.
Besides the hash value, it has parameters:

### Request parameters

* `ttl` (optional) — a JWT lifetime in seconds.
* `claims` (optional) — an array of user attributes names which should be included in the JWT
  payload (if they exist for the user). If a claim ends with `[]` (square braces), user attribute
  named without braces will be included in the JWT payload as an array; if the attribute was not an
  array, it will be present as a one-element array. If claim does not end with `[]`, the JWT payload
  will contain a scalar value; if user attribute is an array, only the first element will be
  included.
* `jws` (required) — JWT signature parameters:
    * `alg` (required) — a signature algorithm which should be used for a JWT sending to the
      endpoint according [RFC 7518](https://www.rfc-editor.org/rfc/rfc7518.html#section-3.1) (except
      "none").
    * `bits` (optional) — a number of bits for generating OpenSSL private key (applicable for
      all algorithms except ECC).
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

JWT Bundle adds to the client registration output a public JWK which can be used further to check a
user JWT signature.

``` yaml
{
    # ...
    "token": {
        "jwk": { ... }
    }
}
```
