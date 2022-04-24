# SingleA Bundle

## Overview

The SingleA bundle is the core bundle in the SingleA project that implements the SingleAuth
framework features. Besides, this bundle provides additional opportunities which significantly
increase security of the SingleA usage and make it reliable authentication service.

The common overview of the SingleA workflow you can find on the [How It Works](../how-it-works.md)
page.

## Prerequisites

You need to configure at least one tag aware cache pool with name "singlea.cache" to avoid an error
at auto-scripts running after the bundle install. You can configure all necessary cache pools at
once. See for details on the [Cache Pool Management](#cache-pool-management) section below.

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
composer require nbgrp/singlea-bundle
```

Also, you need to install the [Symfony Cache](https://symfony.com/doc/current/components/cache.html)
component (or another else the `symfony/cache-contracts` implementation) that is used to
store [user attributes](#user-attributes) (see below).

### Enable the Bundle

If you use Symfony Flex, it enables the bundle automatically. Otherwise, to enable the bundle add
the following code:

``` php title="config/bundles.php"
return [
    // ...
    SingleA\Bundles\Singlea\SingleaBundle::class => ['all' => true],
];
```

## Configuration

Configuration of the SingleA bundle consists of the following groups of parameters.

* `client` — settings for processing in correct way general SingleA requests and the client
  registration requests:
    * `id_query_parameter` (default: "client_id") — the name of the GET parameter that contains the
      client id.
    * `secret_query_parameter` (default: "secret") — the name of the GET parameter that contains the
      client secret.
    * `trusted_clients` — the CSV with the trusted IP addresses / subnets from which general client
      request (user session validation and user token generation) allowed (see
      details [below](#trusted-ip-addresses--subnets)).
    * `trusted_registrars` — the CSV with the trusted IP addresses / subnets from which registration
      requests allowed (see details [below](#trusted-ip-addresses--subnets)).
    * `registration_ticket_header` (default: "X-Registration-Ticket") — the name of the HTTP header
      that can contain the [registration ticket](#registration-ticket) value.
* `ticket` — settings related to user [tickets](#ticket):
    * `cookie_name` (default: "tkt") — the name of the cookie that is set in the response for the
      login or logout request and contains the ticket value.
    * `domain` (**required**) — the value of the `Domain` attribute of the ticket cookie. It must be
      the common (parent) domain for all the client applications using the same SingleA server and
      for the SingleA server itself (e.g. if the client applications have domains `app1.domain.org`
      and `app2.domain.org`, and the SingleA server has domain `sso.domain.org`,
      the `singlea.ticket.domain` parameter should be equal to `domain.org`).
    * `samesite` (default: "lax") — the ticket cookie `SameSite` attribute value.
    * `ttl` (default: 3600) — the ticket lifetime in seconds.
    * `header` (default: "X-Ticket") — the name of the HTTP header in the client requests that
      contains the ticket value.
* `authentication` — settings related to the authentication functions (login and logout):
    * `sticky_session` (default: false) — whether the SingleA user session should
      be [sticky](#sticky-sessions) or not.
    * `redirect_uri_query_parameter` (default: "redirect_uri") — the name of the GET parameter,
      which contains the URI to which the user should be redirected after a successful login or
      logout.
* `signature` — settings related to the [Request Signature](../features/signature.md) feature:
    * `request_ttl` (default: 60) — the client request timeout in seconds: maximum amount of time
      that can elapse between the client request initiation and this request processing by the
      SingleA server.
    * `signature_query_parameter` (default: "sg") — the name of the GET parameter that contains the
      value of the request signature.
    * `timestamp_query_parameter` (default: "ts") — the name of the GET parameter that contains the
      value of the request initiation timestamp.
    * `extra_exclude_query_parameters` — a list of GET parameter names to be excluded from request
      signature validation (e.g. some technical or marketing parameters).
* `encryption` — settings for [client feature configs](#client-feature-configs-encryption)
  and [user attributes](#user-attributes-encryption) encryption:
    * `client_keys` (**required**) — a list of the sodium 256-bit keys that using to encrypt/decrypt
      client feature configs (read
      about [client feature configs encryption](#client-feature-configs-encryption) below).
    * `user_keys` (**required**) — a list of the sodium 256-bit keys that using to encrypt/decrypt
      user attributes (read about [user attributes encryption](#user-attributes-encryption) below).
* `realm` — settings of the [Realms](#realms):
    * `default` (default: "main") — the realm to use if the current request does not contain the GET
      parameter with an explicit realm value.
    * `query_parameter` (default: "realm") — the name of the GET parameter that can contain the
      necessary realm name.
* `marshaller` and `user_attributes` — groups consisting of the only `use_igbinary` parameter which
  setting to use or not the [igbinary](https://www.php.net/manual/en/book.igbinary.php) extension
  for serialization of client feature configs and user attributes. By default, if the igbinary
  extension is available and the extension version is greater than 3.2.2, the igbinary functions
  will be used for serialization.

### Routes

Update your routes configuration:

``` yaml title="config/routes.yaml"
singlea:
    resource: '@SingleaBundle/Resources/config/routes.php'
```

### Trusted IP addresses / subnets

The values of the `singlea.client.trusted_clients` and `singlea.client.trusted_registrars`
parameters can contain comma-separated list of IP addresses or subnets from which appropriate
requests are allowed. Also, it is possible to use the `REMOTE_ADDR` value to allow request from any
host. This approach was inspired by Symfony's `framework.trusted_proxies`
parameter (see an article
[How to Configure Symfony to Work behind a Load Balancer or a Reverse Proxy](https://symfony.com/doc/current/deployment/proxies.html)
from Symfony documentation).

### Sodium encryption keys

It is possible to use together the Symfony build-in environment variable processor `csv` and SingleA
custom processor `base64-array` to pass the CSV-encoded list of base64-encoded sodium keys
(generated by the `sodium_crypto_secretbox_keygen()` function) to the `singlea.encryption.*`
parameters:

``` yaml title="config/packages/singlea.yaml"
singlea:
    # ...
    encryption:
        client_keys: '%env(base64-array:csv:CLIENT_KEYS)%'
        user_keys: '%env(base64-array:csv:USER_KEYS)%'
```

## Client Registration

### Request Parameters

The SingleA bundle uses `signature` as the name (key) of an object with configuration of the Request
Signature feature in the client registration request, which includes the following parameters:

* `md-alg` (required) — the name of
  the [signature algorithm](https://www.php.net/manual/en/openssl.signature-algos.php) to be used to
  sign requests. The value should contain the available constant name without
  the `OPENSSL_ALGO_` prefix (e.g. "SHA256").
* `key` (required) — the public key in PEM format to be used to verify the signature of the request
  being processed.
* `skew` (optional) — the difference between the server and the client system time in seconds (the
  server time minus the client time).

Example:

``` yaml
{
    # ...
    "signature": {
        "md-alg": "SHA256",
        "key": "MI...",
        "skew": 7200
    }
}
```

### Output

The SingleA bundle adds the generated client id and secret to the client registration output.

``` yaml
{
    # ...
    "client": {
        "id": "...",
        "secret": "..."
    }
}
```

## Details

Let's take a closer look at some of SingleA's capabilities.

### User Tokens

When you are building multiple applications with the same authentication point, often the question
of access to user data significantly increase the complexity of development and even becomes a
headache. The special case of such applications are microservices.

The more applications there are, the more relevant become the following questions:

- How to pass into the application the only necessary user data (and do not pass redundant data)?
- How to transmit them safely?
- How to add a new application with the minimal cost of time and without involving additional
  specialists?
- How to achieve all this without performance penalty as increase the number of applications?

SingleA offers User Tokens as an answer for all of these questions. The user token is a unique
string that allows to get user data on the client application side. Meanwhile, a different token is
generated for each application and each of them provides only the data necessary for this
application. The simplest and commonly used way is to use
JWT ([JSON Web Token](https://tools.ietf.org/html/rfc7519)), which allows passing user data encoded
directly in the token. This type of user tokens will be described below.

!!! note ""

    Besides, you can create your own implementation of
    the [Tokenization Feature](../features/tokenization.md), which will generate tokens by your
    logic. For example, as a token you can use a unique key that allows a client application to get
    user data from some shared storage where the SingelA server puts the data during the token
    generation request.

The SingleA project includes the [JWT bundle](jwt.md) (package `nbgrp/singlea-jwt`) for generating
user tokens in the JWT format. It allows transmitting user data as a part of the JWT payload using
of mandatory signature ([RFC 7515](https://www.rfc-editor.org/rfc/rfc7515.html): "JSON Web Signature
(JWS)") to prevent data forgery and optional encryption
([RFC 7516](https://www.rfc-editor.org/rfc/rfc7516.html): "JSON Web Encryption (JWE)") to prevent
sensitive data leaks.

The token can have a lifetime that allows proxy servers and the client application to cache it. This
leads to reduce the SingleA server load and solve the performance problem. It is especially actual
when the Payload Fetcher feature is used (see below).

When registering a client, the `token` parameters set can contain a user claims list — names of
[user attributes](#user-attributes) which must be included in the token payload (see
[Client Registration > Request Parameters](jwt.md#request-parameters) for more details about the
`token` parameters). But there are cases when this data is not enough, and it is necessary to add
some extra data that is known only to an external service (e.g. data based on some business-specific
logic). In this case it is possible to use the [Payload Fetcher](../features/payload-fetcher.md)
feature, which allows the SingleA server to send an additional HTTP request to an external service
with transmitting there a set of user attributes and getting as a response additional data that
should be merged into a final token payload.

The SingleA project contains 2 implementations of the Payload Fetcher feature:

* [JSON Fetcher](json-fetcher.md) — getting user data via simple POST request with transmitting data
  without any protection (as clear text). It is acceptable when you are absolutely sure of the
  security of the communication channel between the SingleA server and the service to which the
  request will be sent.
* [JWT Fetcher](jwt-fetcher.md) — data transmitting as a part of a JWT payload with using mandatory
  signature (JWS) and optional encryption (both of request and response data).

### User Attributes

The user properties that is using to compose user token payload or a data set which is sending to an
external service to retrieve an extra payload data, are named User Attributes in SingleA terms. It
is a key-value structure, where the "key" is a user claim and the "value" is a scalar or an array
that is fetched or calculated at the `SingleA\Bundles\Singlea\Event\UserAttributesEvent` handling,
which is raised at the `Symfony\Component\Security\Http\Event\LoginSuccessEvent` handling.

User attributes are stored as a tagged Symfony Cache items. This is due to the following reasons.

1. SingleA is not intend persistence of any user data, only temporary caching for the user session
   lifetime. The data must be "forgotten" by the SingleA server after the cache item expired or if
   the user was logged out. For this reason the user session lifetime is equal to the cache item
   lifetime that controlled by the Symfony Cache settings
   (see below about the [cache pool management](#cache-pool-management)).
2. The Symfony Cache component provides a convenient way to encrypt cache data and a reach set of
   supported
   [cache adapters](https://symfony.com/doc/current/components/cache.html#available-cache-adapters).
3. With help of cache tags, it is possible to forcibly log the user out using the
   command `user:logout <identifier>`.

!!! caution "Redis and tags"

    Keep in mind that the rows for outdated cache items in tags hash sets will not be removed by
    the command `cache:pool:prune`. You need to erase them by yourself.

    Besides, you need to allocate enough memory for cache items (read about
    [memory allocation](https://redis.io/docs/reference/optimization/memory-optimization/#memory-allocation)
    and [key eviction](https://redis.io/docs/manual/eviction/) in the Redis documentation).

User data is stored in the cache in encrypted form. The user attributes values encrypting using
the `sodium_crypto_secretbox()` function, which takes as arguments the first key from the parameter
`singlea.encryption.user_keys` and [user ticket](#ticket) (which will be considered in detail
below). Unlike a use of the service `Symfony\Component\Cache\Marshaller\SodiumMarshaller` for
[Encrypting the Cache](https://symfony.com/doc/current/cache.html#encrypting-the-cache), this way
allows encrypting user data simultaneously with a rotatable sodium keys and a personal "secret" that
known the only user.

!!! note ""

    The remaining keys from the parameter are used to decrypt previously stored values. Read
    about these keys [above](#sodium-encryption-keys).

#### Cache pool management

As it was written above, the user session lifetime is equal to the lifetime of the cache item that
keeps user attributes. But which cache pool is used to store this cache item?

SingleA uses a separate pool for user attributes for each [Realm](#realms) (what is the Realm will
be considered below). You can configure each pool explicitly: use the
pattern `singlea.cache.[realm]` to name cache pools.

The cache pool for each realm that was not configured explicitly will be created based on the
special cache pool named `singlea.cache` that should be configured in this case (when there is any
realm without an explicitly configured cache pool).

!!! attention ""

    It is necessary to use tag aware cache adapters for cache pools that will be used to store user
    attributes, because tags is used to tag cache items with the user's identifier and make able to
    forcibly log the user out by the command `user:logout <identifier>`.

The cache item lifetime (which was written about a little above) is configured by
the `default_lifetime` cache pool parameter.

!!! abstract ""

    User attributes are common for each client application so there is no meaning to set cache item
    lifetime in any other way, e.g. using the `Psr\Cache\CacheItemInterface::expiresAt` method.

### Ticket

The Ticket is a unique string that is generated at the user successful authentication (at
the `Symfony\Component\Security\Http\Event\LoginSuccessEvent` handling). The ticket resembles a user
session identifier: it is a 192-bit random string that is used as an argument `nonce` in
the `sodium_crypto_secretbox()` and `sodium_crypto_secretbox_open()` functions. Also, in a pair with
the current realm (the firewall name as it described below) the ticket acts as a key for access to
the cache item that keeps attributes of the user.

The ticket value is transmitted to the user as a cookie and must be accessible for a client
application (or a [SingleA client](../client/concept.md)). To achieve this goal, the `Domain` cookie
attribute should be set to a domain that is common for all client applications and the SingleA
server. For example, if applications work with domains `app1.domain.org` and `app2.domain.org`, and
the SingleA server works with a domain `sso.domain.org`, the ticket cookie `Domain` attribute should
be equal to `domain.org`.

Besides, if the client application handle "non-same-origin" requests, it is necessary to set
a `none` as a value of the `singlea.ticket.samesite` parameter.

!!! caution: ""

    In this case all the client applications and the SingleA instance should work over the HTTPS
    protocol, because the ticket cookie must have the `Secure` attribute.

Only the user knows the ticket value (it does not store on the SingleA side), so there is no point
in stealing data from the cache storage. But even if some user's attributes were compromised, it
does not make able to compromise any other user attributes. Coupled with rotatable encryption keys
on the SingleA server side (which the best way to keep in an isolated secrets storage), tickets
provide a reliable protection of user data.

### Lifetimes: User Attributes, Tickets, Tokens

`User Attributes TTL > Ticket TTL > Token TTL`

The expression above reflects the next idea: configure the ticket lifetime less than the user
attributes lifetime and greater than the token lifetime. When the token expires, the ticket acts as
a refresh token and makes able to request new token. When the ticket expires, the user will be
redirected to a logging in and, if a Symfony (PHP) session is still alive and the user attributes
were not expired, the ticket will be regenerated and the user will be redirected back without any
interactive login process.

!!! note ""

    Even if the PHP session was expired, it does not matter for intercommunication between the
    client application (or the SingleA client) and the SingleA server, because only the cache item
    with user attributes is necessary for client request processing.

### Realms

The Realm is an authentication point on the SingleA server side. Actually it is a Symfony firewall.
The user or client request can include a GET parameter that determines which realm (firewall) should
be used for the request processing. This makes it possible to choose the necessary user provider.
Thanks to the session fixation strategy `SessionAuthenticationStrategy::MIGRATE` (see
the [Symfony documentation](https://symfony.com/doc/current/reference/configuration/security.html#session-fixation-strategy)
about the `security.session_fixation_strategy` parameter) it is possible to be authenticated via
multiple firewalls at the same time. Since user attributes store in a cache item which key based on
the realm and ticket values, the user attributes received from different user providers are
independent and can contain different values.

To select the appropriate realm, the best way to use the `request_matcher` firewall field in the
security settings and the special `SingleA\Bundles\Singlea\Request\RealmRequestMatcher` service in
the following way:

``` yaml title="config/packages/security.yaml"
security:
    # ...
    firewalls:
        main:
            # Use the service FQCN and the firewall name separated by a dot
            request_matcher: SingleA\Bundles\Singlea\Request\RealmRequestMatcher.main
```

If you prefer native PHP configuration format, you can do the same in the following way:

``` php title="config/packages/security.php"
<?php

use SingleA\Bundles\Singlea\Request\RealmRequestMatcher;
use Symfony\Config\SecurityConfig;

return static function (SecurityConfig $config): void {
    // ...
    $mainFirewall = $config->firewall('main');
    $mainFirewall->requestMatcher(RealmRequestMatcher::for('main'));
}
```

!!! example

    Thanks to the realms it is possible to organize an access to your corporate application for
    users from an external Identity Provider service (e.g. Active Directory of your business
    partner) without the need to create accounts for them in your corporate Identity Provider
    service or setup their proxying.

### Sticky Sessions

As mentioned above, the user session lifetime is equal to the lifetime of the cache item that keeps
user attributes. When this cache item expires, the user will be forced to log in again (because this
is the only way to compose user attributes). Even if the PHP session was not expired, it will be
invalidated and the user will be logged out forcibly.

But since the default behavior in the Symfony framework assumes to prolong the session lifetime when
you interact with it, there is the `singlea.authentication.sticky_session` parameter
(default: `false`) that makes it possible to prolong the lifetime of the cache item that keeps user
attributes when the user logging in (even if the user already was authenticated).

!!! caution ""

    General request to the SingleA server do not prolong the lifetime of the cache item with user
    attributes in contrast with the PHP session lifetime, which is increased using the Symfony
    framework.

!!! note ""

    Using of Sticky Sessions may lead to the case when user attributes is not updated for a long
    time because they are reloaded at the successful user login (at
    the `Symfony\Component\Security\Http\Event\LoginSuccessEvent` handling).

!!! note ""

    Regardless of the use of Sticky Sessions, after the ticket cookie expires the user will be
    redirected to log in.

## Events

There are several events that can be used to customize the behavior of the SingleA server.

### LoginEvent

!!! example ""

    FCQN: `SingleA\Bundles\Singlea\Event\LoginEvent`

This event is instantiated in the `Login` controller (`/login`) and used for a Response creation and
any additional actions. In particular, the build-in `LoginEvent` listener adds a ticket cookie in
the Response and prolongs the cache item that keeps user attributes (if
the [Sticky Sessions](#sticky-sessions) is used).

### UserAttributesEvent

!!! example ""

    FCQN: `SingleA\Bundles\Singlea\Event\UserAttributesEvent`

As mentioned above, this event is instantiated when the user logged in successfully and is used to
compose user attributes. You need to create your own `UserAttributesEvent` listener or subscriber,
otherwise user attributes will be empty.

!!! tip ""

    This is applicable if you are not going to use the SingleA instance for a user tokens generation
    (only for authentication and user session validation), or if you are going to fetch the whole
    token payload from an external service using the
    [Payload Fetcher](../features/payload-fetcher.md) without passing user attributes.

You can use the `SingleA\Bundles\Singlea\Service\Realm\RealmResolver` service to determine the
current realm (firewall name).

### PayloadComposeEvent

!!! example ""

    FCQN: `SingleA\Bundles\Singlea\Event\PayloadComposeEvent`

This event allows you to modify basic user token payload (before a fetch additional payload from an
external service, if it is used by the client). In some cases this approach can replace the Payload
Fetcher use.

## Customization

The most SingleA classes declared as `final`, so they cannot be extended explicitly.

SingleA customization is able in two ways.

* By using a
  [service decoration](https://symfony.com/doc/current/service_container/service_decoration.html).
* You can implement the necessary service interface and override it in
  the [service container](https://symfony.com/doc/current/components/dependency_injection.html)
  configuration (`config/services.yaml`).

## Security

SingleA security is given special attention, because often due to security issues attackers gain
access to user data. You can read more about the used security methods on
the [SingleA Security](../security.md) section (and also about the "Achilles heel" of SingleA and
what you should protect yourself).

SingleA protective equipment can be divided into 2 groups: mandatory, which managed by the SingleA
configuration, and optional, the use of which is up to you. Let's take a closer look at them below.

### Access Control Expression

SingleA includes a security Expression Language Provider that adds the following functions. They can
be used in an `allow_if` option (read more
about [Securing by an Expression](https://symfony.com/doc/current/security/access_control.html#securing-by-an-expression))
of a `security.access_control` rule.

* `is_valid_signature()` — check whether the current request has a valid signature.
  The [Request Signature](../features/signature.md) feature should be enabled for a client,
  otherwise the check will not be performed and validation will be considered passed.
* `is_valid_ticket()` — check whether the current request has an HTTP header with a valid ticket.

!!! important ""

    Here the ticket validity do not consider an existence of user attributes that relate to the
    ticket. The ticket consider as valid if it exists and has a valid format.

* `is_valid_client_ip()` — check whether the current request IP address is allowed according to the
  parameter `singlea.client.trusted_clients` (see details [above](#trusted-ip-addresses--subnets)).
* `is_valid_registration_ip()` — check whether the current request IP address is allowed according
  to the parameter `singlea.client.trusted_registrars` (see
  details [above](#trusted-ip-addresses--subnets)).
* `is_valid_registration_ticket()` — check whether the current request has an HTTP header with a
  valid [registration ticket](#registration-ticket).

#### Registration Ticket

In addition to the ability to restrict access to the registration route (controller) by the request
sender IP address (or subnet), SingleA make it possible to use Registration Tickets — strings that
should be passed via an HTTP header and verified by the service that implements
the `SingleA\Bundles\Singlea\Service\Client\RegistrationTicketManagerInterface`.

The `is_valid_registration_ticket()` expression language function, as any other, can be used
together with other functions using logical operations `or`/`and`. For example, to restrict a
registration request by an IP address/subnet **or** registration ticket, you can use the following
expression:

```
is_valid_registration_ip() or is_valid_registration_ticket()
```

### Encryption

As described above, SingleA stores client feature configs and user attributes in encrypted form. In
both cases, sets of rotatable keys are used together with secrets (client **secret** and user
**ticket**) that are known for the client or user only.

#### Client Feature Configs Encryption

For client feature configs encryption used keys, which is generated by the
`sodium_crypto_secretbox_keygen()` function and a client secret (that is known for the client only).
The keys are more convenient to keep as a comma-separated base64-encoded list in an environment
variable that can be passed in the parameter `singlea.encryption.client_keys` using the
`%env(base64-array:csv:CLIENT_KEYS)%` expression (if the environment variable called `CLIENT_KEYS`).

The first key from the list always used for encryption. The remaining keys (with the first too) are
used in turn when decrypting the stored value. Therefore, a new key should always be added at the
beginning of the list.

#### User Attributes Encryption

The principle of user attributes encryption is the same as
described [above](#client-feature-configs-encryption) for client feature configs, with only
difference that the keys present in the `singlea.encryption.user_keys` parameter and as a secret is
used user ticket (which is transmitted via a cookie and is known for the user only). Then the ticket
must be passed to the SingleA server via an HTTP header of a client request.

!!! tip ""

    The Symfony Cache component allows you to use the service
    `Symfony\Component\Cache\Marshaller\SodiumMarshaller` for
    [cache items encryption](https://symfony.com/doc/current/cache.html#encrypting-the-cache). This
    approach cannot replace SingleA requirement for user attributes encryption, but it will be
    useful if you are going to store in the cache some other sensitive data except user attributes.
