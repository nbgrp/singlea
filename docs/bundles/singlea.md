# SingleA Bundle

## Overview

SingleA Bundle is the core bundle in the SingleA project that implements the SingleAuth framework
features. Besides, this bundle provides additional opportunities which significantly increase
security of the SingleA usage and make it the universal authentication service.

The common overview of the SingleA workflow you can find on the [How It Works](../how-it-works.md)
page.

## Installation

```
composer require nbgrp/singlea-bundle
```

If you use Symfony Flex it enables the bundle automatically. Otherwise, to enable the bundle add the
following code:

``` php title="config/bundles.php"
return [
    // ...
    SingleA\Bundles\Singlea\SingleaBundle::class => ['all' => true],
];
```

Also, you need to install the Symfony Cache component that is used to store
[user attributes](#user-attributes) (see below).

## Configuration

Configuration of the SingleA Bundles consists of the following groups of parameters.

* `client` — settings for processing in correct way general SingleA requests and the client
  registration requests:
    * `id_query_parameter` (default: "client_id") — the name of the GET parameter that should
      contain the client id.
    * `secret_query_parameter` (default: "secret") — the name of the GET parameter that should
      contain the client secret.
    * `trusted_clients` — the CSV with the trusted IP addresses / subnets from which general client
      request (user session verification and user token generation) allowed (see
      details [below](#trusted-ip-addresses--subnets)).
    * `trusted_registrars` — the CSV with the trusted IP addresses / subnets from which registration
      requests allowed (see details [below](#trusted-ip-addresses--subnets)).
    * `registration_ticket_header` (default: "X-Registration-Ticket") — the name of the HTTP header
      that should contain the [registration ticket](#registration-ticket) value.
* `ticket` — settings related to user [tickets](#ticket):
    * `cookie_name` (default: "tkt") — the name of the cookie that is set in the response for the
      login or logout request and contains the ticket value.
    * `domain` (**required**) — the value of the `Domain` attribute of the ticket cookie. It must be
      the common (parent) domain for all the client applications using the same SingleA server and
      for the SingleA server itself (e.g. if client applications have domains `app1.domain.org`
      and `app2.domain.org`, and the SingleA server has domain `sso.domain.org`,
      the `singlea.ticket.domain` parameter must be equal to `domain.org`).
    * `samesite` (default: "lax") — the ticket cookie `SameSite` attribute value.
    * `ttl` (default: 3600) — the ticket lifetime in seconds.
    * `header` (default: "X-Ticket") — the name of the HTTP header in the client requests that
      should contain the ticket value.
* `authentication` — settings related to the authentication functions (login and logout):
    * `sticky_session` (default: false) — should be the SingleA user
      session [sticky](#sticky-sessions) or not.
    * `redirect_uri_query_parameter` (default: "redirect_uri") — the name of the GET parameter,
      which should contain the URI to which the user must be redirected to after successfully
      logging in or logging out.
* `signature` — settings related to the [Request Signature](../features/signature.md) feature:
    * `request_ttl` (default: 60) — the client request timeout in seconds: maximum amount of time
      that can elapse between the client request initiation and this request processing by the
      SingleA server.
    * `signature_query_parameter` (default: "sg") — the name of the GET parameter that should
      contain the value of the request signature.
    * `timestamp_query_parameter` (default: "ts") — the name of the GET parameter that should
      contain the value of the request initiation timestamp.
    * `extra_exclude_query_parameters` — a list of the GET parameters names that must be excluded
      from request signature validation (e.g. some technical or marketing parameters).
* `encryption` — settings for [clients feature configs](#client-feature-config-encryption)
  and [user attributes](#user-attributes-encryption) encryption:
    * `client_keys` (**required**) — a list of the sodium 256-bit keys that using to encrypt/decrypt
      clients feature configs (read
      about [clients feature configs encryption](#client-feature-config-encryption) below).
    * `user_keys` (**required**) — a list of the sodium 256-bit keys that using to encrypt/decrypt
      user attributes (read about [user attributes encryption](#user-attributes-encryption) below).
* `realm` — settings of the [Realms](#realms):
    * `default` (default: "main") — the name of the realm if the current request does not contain
      the GET parameter with an explicit realm value.
    * `query_parameter` (default: "realm") — the name of the GET parameter that should contain the
      necessary realm name.
* `marshaller` and `user_attributes` — groups consisting of the only `use_igbinary` parameter which
  setting to use or not the igbinary extension for serialization of clients feature configs and user
  attributes. By default, if the igbinary extension is available and the extension version is
  greater than 3.2.2, the igbinary functions will be used for serialization.

Also, you need to configure the Symfony Cache component.
See [Cache Pool Management](#cache-pool-management) section below.

### Trusted IP addresses / subnets

The values of the `singlea.client.trusted_clients` and `singlea.client.trusted_registrars`
parameters can contain comma-separated list of IP addresses or subnets from which appropriate
requests are allowed. Also, it is possible to use the `REMOTE_ADDR` value to allow request from any
host. This approach was inspired by Symfony's `framework.trusted_proxies`
parameter (see an
article [How to Configure Symfony to Work behind a Load Balancer or a Reverse Proxy](https://symfony.com/doc/current/deployment/proxies.html)
from Symfony documentation).

### Sodium encryption keys

It is possible to use together the Symfony build-in environment variable processor `csv` and SingleA
custom processor `base64-array` to pass the CSV-encoded list of base64-encoded sodium keys
(generated by the `sodium_crypto_secretbox_keygen` function) to the
`singlea.encryption.*` parameters:

``` yaml title="config/packages/singlea.yaml"
singlea:
    # ...
    encryption:
        client_keys: '%env(base64-array:csv:CLIENT_KEYS)%'
        user_keys: '%env(base64-array:csv:USER_KEYS)%'
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
directly in the token. Further will be described this type of user tokens.

!!! note ""

    Besides, you can create your own implementation of
    the [Tokenization Feature](../features/tokenization.md), which will generate tokens by your
    logic. For example, as a token you can use a unique key that allows a client application to get
    user data from any shared storage where the SingelA server put this data during the token
    generation request.

The SingleA project includes the [JWT Bundle](jwt.md) (package `nbgrp/singlea-jwt`) for generating
user tokens in the JWT format. It allows transmitting user data as a part of the JWT payload using
of mandatory signature ([RFC 7515](https://www.rfc-editor.org/rfc/rfc7515.html): "JSON Web Signature
(JWS)") to prevent data forgery and optional encryption
([RFC 7516](https://www.rfc-editor.org/rfc/rfc7516.html): "JSON Web Encryption (JWE)") to prevent
sensitive data leaks.

A token can have a lifetime that allows proxy servers and the client application to cache it. This
leads to reduce the SingleA server load and solve the performance problem. It is especially actual
when the Payload Fetcher is used (see below).

When registering the client, the `token` parameters set can contain a user claims list — names of
[user attributes](#user-attributes) which must be included in the token payload (see
[Client Registration > Request Parameters](jwt.md#request-parameters) for more details about `token`
parameters). But there are cases when this data is not enough, and it is necessary to add some extra
data that is known only to an external service (e.g. data based on some business-specific logic). In
this case it is possible to use the [Payload Fetcher](../features/payload-fetcher.md) feature, which
allow the SingleA server to make an additional HTTP request to an external service with transmitting
there a set of user attributes and getting as a response additional data that must be merged into a
final token payload.

The SingleA project contains 2 implementations of the Payload Fetcher feature:

* [JSON Fetcher](json-fetcher.md) — getting user data via simple POST request with transmitting data
  without any protection (as clear text). It is acceptable when you are absolutely sure of the
  security of the communication channel between the SingleA server and the service the request sent
  to.
* [JWT Fetcher](jwt-fetcher.md) — data transmitting as a part of a JWT payload with using mandatory
  signature (JWS) and optional encryption (both of request and response data).

### User Attributes

The user properties that is using to compose user token payload or a data set which is sending to an
external service to retrieve an extra payload data, are named User Attributes in SingleA terms. It
is a key-value structure, where the "key" is a user claim and the "value" is a scalar or an array
that fetched or calculated during `SingleA\Bundles\Singlea\Event\UserAttributesEvent` handling,
which rising during `Symfony\Component\Security\Http\Event\LoginSuccessEvent` handling.

User Attributes stored as a tagged Symfony Cache items. This is due to the following reasons.

1. SingleA is not intend persistence of any user data, only temporary caching for a user session
   lifetime. The data must be "forgotten" by the SingleA server after the cache item expired or if
   the user was logged out. For this reason the user session lifetime is equal to a cache item
   lifetime that controlled by the Symfony Cache settings
   (see below about [cache pool management](#cache-pool-management)).
2. The Symfony Cache component provides a convenient way to encrypt cache data and a reach set of
   supported
   [cache adapters](https://symfony.com/doc/current/components/cache.html#available-cache-adapters).
3. With help of cache tags it is possible to log the user out forcibly via the CLI
   command `user:logout <identifier>`.

!!! caution "Redis and tags"

    Keep in mind that the rows for outdated cache items in tags hash sets will not be removed by
    the CLI command `cache:pool:prune`. You need to erase them by yourself.

    Besides, you need to allocate enough memory for cache items (read about
    [memory allocation](https://redis.io/docs/reference/optimization/memory-optimization/#memory-allocation)
    in Redis documentation).

User data is stored in the cache in encrypted form. The user attributes values encrypting with help
of the `sodium_crypto_secretbox` function, the first key from the parameter
`singlea.encryption.user_keys` and [user ticket](#ticket) (which will be considered in detail below)
. Unlike a use of the service `Symfony\Component\Cache\Marshaller\SodiumMarshaller` for
[Encrypting the Cache](https://symfony.com/doc/current/cache.html#encrypting-the-cache), this way
allows encrypting user data simultaneously with a rotatable sodium keys and a personal "secret" that
known the only user.

!!! note ""

    The remaining keys from the parameter are used to decrypt previously stored values. Read
    about these keys [above](#sodium-encryption-keys).

#### Cache pool management

As it was written above, the user session lifetime is equal to the lifetime of cache item that keep
user attributes. But what cache pool is used to store this cache item?

SingleA uses separate pools for user attributes for every single [Realm](#realms) (what is the Realm
will be considered below). You can configure each pool explicitly: use the pattern
`singlea.cache.[realm]` to name cache pools.

Cache pools for every realm that was not configured explicitly will be created based on the special
cache pool named `singlea.cache` that should be configured if there are realms that was not
configured explicitly.

!!! attention ""

    It is necessary to use tag aware cache adapters for cache pools that will be used to store user
    attributes, because tags is used to tag cache items with user identifier and make able to log
    the user out forcibly by the CLI command `user:logout <identifier>`.

The cache item lifetime (which was written about a little above) is configured by
the `default_lifetime` cache pool parameter.

!!! abstract ""

    User attributes are common for each client application so there is no meaning to set cache item
    lifetime in any other way, e.g. via `Psr\Cache\CacheItemInterface::expiresAt` method.

### Ticket

The Ticket is a unique string that is generated during the user successful authentication
(`Symfony\Component\Security\Http\Event\LoginSuccessEvent` handling). Ticket resembles a user
session identifier: it is a 192-bit random string that is used as an argument `nonce` in
the `sodium_crypto_secretbox` and `sodium_crypto_secretbox_open` functions. Also, in pair with a
current realm (firewall name as it described below) the ticket acts as a key for access to the cache
item that keep attributes of this user.

The ticket value is transmitted to the user as a cookie and must be accessible for a client
application (or a [SingleA client](../client/concept.md)). To achieve this a cookie
attribute `Domain` must be set to the common for all client applications and the SingleA server
domain. For example, if applications work with domains `app1.domain.org` and `app2.domain.org`, and
the SingleA server work with domain `sso.domain.org`, the ticket cookie `Domain` must be
equal `domain.org`.

Besides, if the client application handle "non-same-origin" requests, it is necessary to set
a `none` as a value of the `singlea.ticket.samesite` parameter.

!!! caution: ""

    In this case all the client applications and SingleA instance must work via the HTTPS protocol,
    because the ticket cookie must have the `secure` attribute.

Only the user knows the ticket value (it does not store on the SingleA side), so there is no point
in stealing data from the cache storage. But even if some user attributes were compromised, it does
not make able to compromise any other user attributes. Coupled with rotatable encryption keys on a
SingleA server side (which the best way to keep in an isolated secrets storage), tickets provide a
reliable protection of user data.

### Lifetimes: User Attributes, Tickets, Tokens

`User Attributes TTL > Ticket TTL > Token TTL`

The expression above reflects the next idea: configure a ticket lifetime less than a user attributes
lifetime and greater than a token lifetime. When the token expires, the ticket acts as a refresh
token and makes able to request new token. When the ticket expires, the user will be redirected to a
logging in and, if a Symfony (PHP) session is still alive and the user attributes was not expired,
the ticket will be regenerated and user will be redirected back without any interactive login
process.

!!! note ""

    Even if the PHP session was expired, it does not matter for intercommunication between the
    client application (or the SingleA client) and the SingleA server, because only the cache item
    with user attributes is necessary for client request processing.

### Realms

The Realm is an authentication point on the SingleA side. Actually it is a Symfony firewall. The
user or client request can include a GET parameter that determines what realm (firewall) must be
used for the request processing. This makes it possible to choose a necessary user provider. Thanks
to the session fixation strategy `SessionAuthenticationStrategy::MIGRATE` (see
the [Symfony documentation](https://symfony.com/doc/current/reference/configuration/security.html#session-fixation-strategy)
about `security.session_fixation_strategy` parameter) it is possible to be authenticated via
multiple firewalls at the same time. Since user attributes store in a cache item which key based on
the realm and ticket values, the user attributes received from different user providers are
independent and can contain different values.

!!! example

    Thanks to the realms it is possible to organize access to some corporate application for users
    from some external Identity Provider service (e.g. Active Directory of your business partner)
    without the need to create accounts for them in your corporate Identity Provider service or
    setup their proxying.

### Sticky Sessions

As mentioned above, user session lifetime is equal to a lifetime of the cache item with the user
attributes. When this cache item will expire the user will be forced to log in again (because this
is the only way to compose a set of user attributes). Even if the PHP session was not expired, it
will be invalidated and the user will be logged out forcibly.

But since the default behavior in Symfony framework assumes to prolong the session lifetime when you
interact with it, there is a parameter `singlea.authentication.sticky_session` (default: `false`)
that make it possible to prolong the lifetime of the cache item with user attributes when the user
logging in (even if the user already was authenticated).

!!! caution ""

    General request to the SingleA server do not prolong the lifetime of the cache item with user
    attributes in contrast with the PHP session lifetime, which is increased using the Symfony
    framework.

!!! note ""

    Using of Sticky Sessions may lead to the case when the user attributes is not updated for a
    long time because they are reloaded during a successful user login
    (when processing `Symfony\Component\Security\Http\Event\LoginSuccessEvent`).

!!! note ""

    Regardless of the use of Sticky Sessions, after the ticket cookie expired the user will be
    redirected to the login.

## Events

There are several events that can be used to customize the behavior of the SingleA server.

### LoginEvent

!!! example ""

    FCQN: `SingleA\Bundles\Singlea\Event\LoginEvent`

This event is instantiated in the `Login` controller (`/login`) and used for a Response creation and
any additional actions. In particular, the build-in `LoginEvent` listener adds a ticket cookie in
the Response and prolongs the cache item with user attributes (if
the [Sticky Sessions](#sticky-sessions) is used).

### UserAttributesEvent

!!! example ""

    FCQN: `SingleA\Bundles\Singlea\Event\UserAttributesEvent`

As mentioned above, this event is instantiated when the user logged in successfully and is used to
compose the user attributes. You need to create your own `UserAttributesEvent` listener or
subscriber, otherwise the user attributes will be empty.

!!! tip ""

    This is applicable if you are not going to use the SingleA for a user tokens generation (only
    for an authentication and user session validation), or if you are going to fetch the whole token
    payload from an external service via [Payload Fetcher](../features/payload-fetcher.md) without
    passing user attributes.

### PayloadComposeEvent

!!! example ""

    FCQN: `SingleA\Bundles\Singlea\Event\PayloadComposeEvent`

This event allows modifying a basic user token payload (before a fetch of additional payload from an
external service, if it is used by the client). In some cases this approach can replace the Payload
Fetcher use.

## Customization

The most SingleA classes declared as `final`, so they cannot be extended explicitly.

SingleA customization is able in two ways.

* You can implement the necessary service interface and override it in
  the [service container](https://symfony.com/doc/current/components/dependency_injection.html)
  configuration (`config/services.yaml`).
* With help of the
  [service decoration](https://symfony.com/doc/current/service_container/service_decoration.html).

## Security

SingleA security is given special attention, because often due to security issues attackers gain
access to user data. You can read more about the used security methods in
the [SingleA Security](../security.md) section (and also about the "Achilles heel" of SingleA and
what you should protect yourself).

SingleA protective equipment can be divided into 2 groups: mandatory, which managed by the SingleA
configuration, and optional, the use of which is up to you. Let's take a closer look at them below.

### Access Control Expression

SingleA includes a security Expression Language Provider that adds the following functions. They can
be used in an `allow_if` option (read more
about [Securing by an Expression](https://symfony.com/doc/current/security/access_control.html#securing-by-an-expression))
of a `security.access_control` record.

* `is_valid_signature` — checks whether the current request has a valid signature.
  The [Request Signature](../features/signature.md) feature should be enabled for a client,
  otherwise the check will not be performed and validation will be considered passed.
* `is_valid_ticket` — checks whether the current request has an HTTP header with a valid ticket.

!!! important ""

    Here the ticket validity do not consider an existence of user attributes that relate to the
    ticket. The ticket consider as valid if it exists and has a valid format.

* `is_valid_client_ip` — checks whether the current request IP address is allowed according to the
  parameter `singlea.client.trusted_clients` (see details [above](#trusted-ip-addresses--subnets)).
* `is_valid_registration_ip` — checks whether the current request IP address is allowed according to
  the parameter `singlea.client.trusted_registrars` (see
  details [above](#trusted-ip-addresses--subnets)).
* `is_valid_registration_ticket` — checks whether the current request has an HTTP header with a
  valid [registration ticket](#registration-ticket).

#### Registration Ticket

In addition to the ability to restrict access to the registration route (controller) by the request
sender IP address (or subnet), SingleA make it possible to use Registration Tickets — strings that
should be passed via an HTTP header and verified by the service that implements
`SingleA\Bundles\Singlea\Service\Client\RegistrationTicketManagerInterface`.

The `is_valid_registration_ticket` expression language function, as any other, can be used together
with other functions via `or`/`and` logical operations. For example, to restrict registration
request by an IP address/subnet **or** registration ticket, you can use the following expression:

```
is_valid_registration_ip() or is_valid_registration_ticket()
```

### Encryption

As described above, SingleA stores clients feature configs and user attributes in encrypted form. In
both cases, sets of rotatable keys are used together with secrets (client **secret** and user
**ticket**) that are known for the client or user only.

#### Client Feature Config Encryption

For clients feature configs encryption used keys, which is generated by the
function `sodium_crypto_secretbox_keygen` and a client secret (that is known for the client only).
The keys are more convenient to keep as comma-separated base64-encoded list in an environment
variable that can be passed in the parameter `singlea.encryption.client_keys` with help of the
expression `%env(base64-array:csv:CLIENT_KEYS)%` (if the environment variable called `CLIENT_KEYS`).

The first key from the list always used for encryption. The remaining keys (with the first too) are
used in turn when decrypting the stored value. Therefore, a new key should always be added at the
beginning of the list.

#### User Attributes Encryption

The principle of user attributes encryption is the same as
described [above](#client-feature-config-encryption) for clients feature configs, with only
difference that keys present in the parameter `singlea.encryption.user_keys` and as a secret is used
user ticket (which is transmitted via a cookie and is known for user only). Then the ticket must be
passed to the SingleA server via an HTTP header of a client request.

!!! tip ""

    The Symfony Cache component allows you to use the service
    `Symfony\Component\Cache\Marshaller\SodiumMarshaller` for
    [cache items encryption](https://symfony.com/doc/current/cache.html#encrypting-the-cache). This
    approach cannot replace SingleA requirement for user attributes encryption, but it will be
    useful if you are going to store in the cache some other sensitive data except user attributes.
