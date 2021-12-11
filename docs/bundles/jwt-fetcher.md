# JWT Fetcher Bundle

## Overview

> Implements `nbgrp/singlea-payload-fetcher-contracts`.

JWT Fetcher Bundle implements receiving an additional user token payload from an external endpoint
via an HTTP request containing JWT with a payload composed of user attributes. Due to Spomky's
[JWT Framework](https://github.com/web-token/jwt-framework/), in addition to a mandatory signature
of the JWT, it can be encrypted (according to JOSE).

## Installation

```
composer require nbgrp/singlea-jwt-fetcher-bundle
```

If you use Symfony Flex it enables the bundle automatically. Otherwise, to enable the bundle add the
following code:

``` php title="config/bundles.php"
return [
    // ...
    SingleA\Bundles\JwtFetcher\SingleaJwtFetcherBundle::class => ['all' => true],
];
```

## Configuration

Requests to http (unsecure) endpoints are denied by default. To permit them, you need to add the
following bundle configuration:

``` yaml title="config/packages/singlea_jwt_fetcher.yaml"
singlea_jwt_fetcher:
    https_only: false
```

The bundle uses default [Symfony HttpClient](https://symfony.com/doc/current/http_client.html). If
you need to pass [scoped](https://symfony.com/doc/current/http_client.html#scoping-client)
HttpClient, you should override the service `SingleA\Bundles\JwtFetcher\JwtFetcher` constructor
argument:

``` yaml title="services.yaml"
services:
    # ...
    SingleA\Bundles\JwtFetcher\JwtFetcher:
        arguments: [ '@custom.client' ]

```

## Client registration

### Request parameters

JWT Fetcher Bundle use `jwt` as hash value to determine own parameters in client registration
request. Besides the hash value, it has parameters:

* `endpoint` (required) — a URL where must be sent a POST request which contains an array of user
  attributes according `claims` parameter (it may be an empty array).
* `claims` (optional) — an array of user attributes names which should be included in the sending
  JWT payload (if they exist for the user). If a claim ends with `[]` (square braces), user
  attribute named without braces will be included in the JWT payload as an array; if the attribute
  was not an array, it will be present as a one-element array. If claim does not end with `[]`, the
  JWT payload will contain a scalar value; if user attribute is an array, only the first element
  will be included.
* `request` (required) — a JSON data structure with the following nested objects which configure
  parameters for generating a JWT that will be sent to the endpoint:
    * `jws` (required) — JWT signature parameters:
        * `alg` (required) — a signature algorithm which should be used for a JWT sending to the
          endpoint according [RFC 7518](https://www.rfc-editor.org/rfc/rfc7518.html#section-3.1)
          (except "none").
        * `bits` (optional) — a number of bits for generating OpenSSL private key (applicable for
          all algorithms except ECC).
    * `jwe` (optional) — JWT encryption parameters:
        * `alg` (required) — a key encryption algorithm according RFC 7518:
          [4. Cryptographic Algorithms for Key Management](https://www.rfc-editor.org/rfc/rfc7518.html#section-4).
        * `enc` (required) — a content encryption algorithm according RFC 7518:
          [5. Cryptographic Algorithms for Content Encryption](https://www.rfc-editor.org/rfc/rfc7518.html#section-5).
        * `zip` (optional) — a JWE compression algorithm according RFC 7518:
          [7.3. JSON Web Encryption Compression Algorithms Registry](https://www.rfc-editor.org/rfc/rfc7518.html#section-7.3).
        * `jwk` (required) — a JSON data structure which represent a recipient public JWK according
          RFC 7518:
          [6. Cryptographic Algorithms for Keys](https://www.rfc-editor.org/rfc/rfc7518.html#section-6).
    * `options` (optional) — a JSON data structure with additional options which will be passed into
      the method `Symfony\Contracts\HttpClient\HttpClientInterface::request`.
* `response` (optional) — a JSON data structure with the following nested objects which configure
  parameters for processing a response JWT:
    * `jws` (required) — a JWT signature parameters:
        * `alg` (required) — a key encryption algorithm according RFC 7518:
          [4. Cryptographic Algorithms for Key Management](https://www.rfc-editor.org/rfc/rfc7518.html#section-4).
        * `jwk` (required) — a JSON data structure which represent a public JWK for a JWT signature
          check according RFC 7518:
          [6. Cryptographic Algorithms for Keys](https://www.rfc-editor.org/rfc/rfc7518.html#section-6).
    * `jwe` (optional) — a JWT encryption parameters:
        * `alg` (required) — a key encryption algorithm according RFC 7518:
          [4. Cryptographic Algorithms for Key Management](https://www.rfc-editor.org/rfc/rfc7518.html#section-4)
          according which will be generated a recipient JWK.
        * `bits` (optional) — a number of bits for generating OpenSSL private key (applicable for
          all algorithms except ECC).
        * `enc` (required) — a content encryption algorithm according RFC 7518:
          [5. Cryptographic Algorithms for Content Encryption](https://www.rfc-editor.org/rfc/rfc7518.html#section-5).
        * `zip` (optional) — a JWE compression algorithm according RFC 7518:
          [7.3. JSON Web Encryption Compression Algorithms Registry](https://www.rfc-editor.org/rfc/rfc7518.html#section-7.3).

!!! tip

    You can use a helpful CLI tool from Spomky's JWT Framework for
    [JWK generation](https://web-token.spomky-labs.com/console-command/console#key-generators).

Example:

``` yaml
{
    # ...
    "payload": {
        "#": "jwt",
        "endpoint": "https://example.domain/_singlea_payload",
        "claims": [
            "email",
            "role[]"
        ],
        "request": {
            "jws": {
                "alg": "RS256",
                "bits": 512
            },
            "jwe": {
                "alg": "A128KW",
                "enc": "A128CBC-HS256",
                "zip": "DEF",
                "jwk": { ... }
            },
            "options": {
                "timeout": 5
            }
        },
        "response": {
            "jws": {
                "alg": "RS256",
                "jwk": { ... }
            },
            "jwe": {
                "alg": "RSA-OAEP-256",
                "bits": 1024,
                "enc": "A256GCM",
                "zip": "DEF"
            }
        }
    }
}
```

### Output

JWT Fetcher Bundle adds to the client registration output the following data:

* `payload.request.jwk` (required) — a public JWK for a request (sent) JWT signature check.
* `payload.response.jwk` (optional) — a recipient public JWK for which a response JWT must be
  encrypted (if `payload.response.jwe` specified in registration data).

``` yaml
{
    # ...
    "payload": {
        "request": {
            "jwk": { ... }
        },
        "response": {
            "jwk": { ... }
        }
    }
}
```
