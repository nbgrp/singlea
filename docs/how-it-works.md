# How It Works

!!! info ""

    The below describes a usage of an official set of SingleA bundles included in the SingleA
    project. Here described a complete way of user token creation including the secured
    [Payload Feature](features/payload-fetcher.md) usage. Step-by-step it will be shown how SingleA
    works and indicated what and when can be done more simply.

## Preparation of the SingleA Instance

### Install Necessary Bundles

The SingleA installation consists of 2 parts:

* installing the `nbgrp/singlea-bundle` package which is the core component;
* and installing required and optional packages (bundles).

SingleA use a modular, [contracts based](features/contracts.md) approach. The SingleA bundle demands
provision of the `nbgrp/singlea-persistence-contracts` and `nbgrp/singlea-tokenization-contracts`
implementations. If you do not have your own implementations of these contracts, you can use
`nbgrp/singlea-redis-bundle` and `nbgrp/singlea-jwt-bundle`. The bundles implement
the [Payload Fetcher](features/payload-fetcher.md) feature are optional.

To provide a complete set of SingleA features which may be provided out-of-box with secured
implementation of Payload Fetcher, assume the following list of bundles was installed:

* `nbgrp/singlea-bundle`
* `nbgrp/singlea-redis-bundle`
* `nbgrp/singlea-jwt-bundle`
* `nbgrp/singlea-jwt-fetcher-bundle`

!!! note ""

    Instead of `nbgrp/singlea-jwt-fetcher-bundle` you can use `nbgrp/singlea-json-fetcher-bundle`,
    but in that case the request and response for additional payload data will contains unprotected
    JSON object. With usage of `singlea-jwt-fetcher-bundle` the data transmitting with help of JOSE
    as signed and (if necessary) in encrypted form.

### Configure Bundles

A detailed description of each bundle configuration can be found on separate pages in the
[Bundles](bundles) section. Here only should be noted that the SingleA bundle includes the settings
for:

* encryption keys for client features configs and user attributes,
* restrictions by IP addresses for registration and general requests,
* a [ticket](singleauth.md#ticket) cookie creation,
* and many others.

The SingleA project was created following [the Twelve-Factor App](https://12factor.net/)
methodology, so environment variables should be used to pass configuration values, if it is
possible.

## Client Registration

To be able to interact with a previously installed and configured SingleA instance, you need to
register your applications. For the description below we will assume that there are 2 client
applications with domains _**app1**.domain.org_ and _**app2**.domain.org_. Both of them must be
registered by the POST request to the `/client/register` endpoint (which can be changed if
necessary). Assume that both of the applications will use
the [Request Signature](features/signature.md), [Tokenization](features/tokenization.md)
and [Payload Fetcher](features/payload-fetcher.md) features.

The registration requests must contain JSON with the following keys and data.

* `signature` (Request Signature) — the name of the message digest algorithm (which supported by the
  PHP OpenSSL extension,
  see [Signature Algorithms](https://www.php.net/manual/en/openssl.signature-algos.php); e.g. "
  SHA256") and the signature public key in PEM format (which will be used to verify the signature of
  the requests to the SingleA server).
* `token` (Tokenization) — JWT creation settings, in particular the JWT lifetime, a list of user
  claims, JWS and JWE settings (see [JWT bundle documentation](bundles/jwt.md) for more details).
* `payload` (Payload Fetcher) — additional payload fetching settings.
  Since `singlea-jwt-fetcher-bundle` is used, it is allowed to use fetch request data signing and
  encrypting. Thus, the `payload` settings are similar to the `token` settings, but can contain JWS
  and JWE settings for both the payload fetch request data and the corresponding response data.

As a response to the registration request, a JSON will be received, which contains the created
**client id** and **secret**, JWK formatted public keys for validation of the user JWT and JWT from
the payload fetch request, and the JWK formatted public key of the recipient of the payload fetch
request for whom it must be encrypted.

### Registrant Restriction

It is possible to restrict who are able to make registration requests using an `allow_if` option of
a `security.access_control` rule in a `config/packages/security.yaml` (the registration request
route is `/client/register` by default). Use the `is_valid_registration_ip()` and
`is_valid_registration_ticket()` expressions for this. As described in the
[Symfony documentation page](https://symfony.com/doc/current/security/access_control.html#matching-access-control-by-ip)
about `security.access_control`, it is necessary to add one more rule to prevent a request
processing according another rule, e.g.:

```
- { path: ^/client/register, allow_if: "is_valid_registration_ip()" }
- { path: ^/client/register, roles: ROLE_NO_ACCESS }
```

!!! hint ""

    The second rule may be omitted, if `security.access_control` rules do not contain other rules
    which will allow a registration request processing.

Trusted IP addresses and subnetworks should be specified in the SingleA bundle settings
(`singlea.client.trusted_registrars`) as CSV formatted string. Similar to the trusted proxies
settings it is possible to specify the `REMOTE_ADDR` value to allow request from any host.

To allow registration using a [registration ticket](bundles/singlea.md#registration-ticket),
it is necessary to use the `is_valid_registration_ticket()` expression function and to implement the
`\SingleA\Bundles\Singlea\Service\Client\RegistrationTicketManagerInterface` (an implementation must
be able to autowire by an interface name).

!!! tip

    All these expressions can be used mutually with `or`/`and` logical operators. In particular,
    registration requests can be restricted by the IP address/subnet or by the registration ticket
    using the expression "`is_valid_registration_ip() or is_valid_registration_ticket()`".

## User Request Processing

To show a complete way of the user request processing towards a client application using SingleA
server, it will be considered the following scenario: at first, not logged-in user interacts with
the first client application (app1.domain.org), and then interacts with the second one
(app2.domain.org). It will show how the user [ticket](singleauth.md#ticket) used by each of them.

Client applications can interact with the SingleA server in any available way, but if they work
behind a nginx web server, it is the easiest way to use
the [SingleA Lua client for nginx](client/nginx.md). It is a lightweight Lua script which need a
nginx server compiled with LuaJIT support and a few additional libraries (see link above for more
details).

The below description will suppose that the client applications are behind the nginx server + the
SingleA Lua client, which has the following 4 methods:

* `login()` — check the ticket cookie existence in the user request and validate the user session on
  the SingleA server side (if the cookie exists), and redirect the user to login on the SingleA
  server if the user has no cookie or the session validation failed;
* `logout()` — redirect the user to the SingleA server for logout;
* `validate()` — validate the user session on the SingleA server side;
* `token(header_name)` — receive the user token from the SingleA server (or from nginx cache, if
  exists) and add it into an HTTP header, which name passed in the `header_name` argument (or
  "Authorization", if not).

All methods, except `logout`, return an instance of the SingleA client and can be used in a call
chain format. Moreover, there are cases when the client application does not need to receive a user
token or to validate a user session (just returning an error when trying to receive a token for
unauthorized user). The following description assumes that the `login` and `token` methods are
chained and a token always is requested for an authorized user.

### Request from Unauthorized User

On the very first request, the user has not yet authenticated on the SingleA server side, therefore
a ticket cookie does not exist. During the request processing the SingleA client checks it and
redirects the user to the SingleA server for a login. The redirect (login) location contains:

* `client_id` and `secret` — which were received at registration;
* `redirect_uri` — a URI where the user should be redirected to after a successful login;
* `signature` — the [request signature](features/signature.md) which prevent the `redirect_uri`
  forgery during the request transmission;
* `timestamp` — the request timestamp which is the mandatory signature component that helps to
  prevent attacks to the SingleA server by valid requests.

!!! info ""

    On every request processing the SingleA server verifies the client id and secret, and checks
    that the client exists.

To verify the request signature it is necessary to use the `is_valid_signature()` expression in an
`allow_if` option of a `security.access_control` rule (`config/packages/security.yaml`). Use it for
the routes that you want to protect with a signature. Protect all SingleA routes is the best way,
for this reason the SingleA Lua client adds signature on every interaction: for login and logout
redirect URLs, user session validation requests and user token receive requests.

The passed signature check happens by the following algorithm.

* Check that the request lifetime has not expired.
* Make an array with GET parameters which must be used as a signature base.
* Concatenate values from the array with a `.` (dot) into a string.
* Check that the passed in the request signature is valid for the resulting string by
  the `openssl_verify` function (with help of the public key and the message digest algorithm
  specified at registration).

See more detailed description of the [signature verification algorithm](features/signature.md). In
particular: how to offset the difference in the SingleA server and the client sides system time, how
to exclude unnecessary GET parameters from the signature base array, and why the user login
interactive duration has no meaning.

!!! attention

    If the user is not authenticated, an invalid signature will lead to a redirect to a login page
    and not to an HTTP error "Forbidden 403".

!!! note ""

    The `/login` route must have the `is_fully_authenticated()` expression (in addition to the
    `is_valid_signature()` expression for a request signature verification) in an `allow_if` option
    of a `security.access_control` rule for a correct behavior.

!!! caution ""

    As noted in the case of the [registrant restriction](#registrant-restriction), it is important
    to add an additional rule to prevent an invalid request processing if there is a rule that
    allows access for anonymous users. Multiple routes may be united in one rule using a regular
    expression:
    ```
    - { path: ^/(login|validate|token), roles: ROLE_NO_ACCESS }
    ```

Between successful signature verification and the user redirect to authentication the initial
request URI will be saved into the session for further use.

As a result of successful authentication, a set of handlers is called, which:

* generate a unique ticket value;
* make the user attributes set using the `\SingleA\Bundles\Singlea\Event\UserAttributesEvent`, which
  will be stored into a firewall (realm) based cache pool tagged by the user identifier;
* if the SingleA bundle parameter `singlea.authentication.sticky_session` is set to `true` and the
  user attributes already stored in the cache pool, the lifetime of them will be increased (as a
  result of deletion and re-saving the user attributes);
* the user redirection to the original `redirect_uri`.

It needs to be explained about increasing the lifetime of the cache item with user attributes and
why it is important. The user attributes are storing in the cache pool only during the
`\Symfony\Component\Security\Http\Event\LoginSuccessEvent` handling and are not if the user already
has logged in.

!!! info ""

    Other actions process in any way, including if the user already authorized. For this reason, if
    you use [sticky sessions](bundles/singlea.md#sticky-sessions), during an authorization request
    the user attributes will be saved twice.

!!! important

    The key of the cache item with user attributes is generated based on the ticket value and
    the realm — the name of the firewall that used for the request processing. Read more about
    [realms](bundles/singlea.md#realms) to understand what it is and when you may need it.

If the authenticated user attributes were removed from the cache pool (because expired, were
manually removed or by some event listener), the user will be logged out and will be redirected to
the `redirect_uri` (from where should be redirected to the login endpoint again because the ticket
cookie was removed as a result of the logout).

### Request from Already Authorized User

Above was described a request processing scenario when the user is not authorized and the request do
not contain ticket cookie. But after a successful login the user will receive this cookie and (if
the SingleA bundle was configured correctly) it will be available to the SingleA client.

If the client application domain (we use app1.domain.org and app2.domain.org) matches the ticket
cookie `Domain` argument (`domain.org` in our example) the ticket value is available for the SingleA
client and the user token (JWT) can be received from the SingleA server via a GET request to
the `/token` route. The ticket value must be specified in the request as an HTTP header "X-Ticket"
(or any other configured in the SingleA bundle `singlea.ticket.header` parameter). The user token
can be received only for the users whose attributes exist in the cache.

!!! note ""

    In addition to the `login()` method of the SingleA Lua client, the `validate()` method can be
    used to check the user session on the SingleA server side. It needs the ticket cookie to be
    available.

The request to receive the user token will be made only in a case when the token is not contained in
a nginx cache (Lua dictionary, more details see below). If the token exists in the cache, it will be
taken from there. In any way the token will be appended as an HTTP header `Authorization` (or other
if the custom header name specified in the `header_name` argument for the `token()` client method)
in the original user request to the client application. In the end the original request passed to
the client application for further processing.

The JWT generation should be described in more detail, especially because in this example we use the
`nbgrp/singlea-jwt-fetcher-bundle` package for receiving additional payload data.

!!! important

    The client application can be registered without passing settings for the user token generation
    (under the `token` key). If so the token receiving request will lead to an HTTP error "Forbidden
    403".

#### JWT Creation

1. Extract from the user attributes to an array the user claims, which was specified in
   the `token.claims` parameter in the client registration request. This is the basic payload.
2. Make an HTTP request, to receive additional payload data, to the external endpoint specified in
   the `payload.endpoint` parameter at the registration:
    1. build a JWT with payload which contains the user attributes according the user claims
       specified in the `payload.claims` parameter in the client registration request;
    2. the JWT is signed with the private key generated for the client during the client
       registration, and is encrypted with the public key from the `payload.request.jwe.jwk`
       parameter specified in the client registration request;
    3. the request with the JWT as a request body is sending to the external endpoint;
    4. the request processing on the endpoint side is out of scope of this description, but it
       should be noted that the response must contain a JWT with a payload, which contains data that
       should be merged with the basic payload;
    5. the response JWT must be signed with the key, which is paired with the specified in
       the `payload.response.jws.jwk` parameter in the client registration request, and must be
       encrypted with the public key which was received in the client registration response in
       the `payload.response.jwe.jwk` parameter (if the response JWT encryption is configured for
       the client application).

The JWT can have a lifetime (an `exp` claim). Since this is configurable by the `token.ttl`
parameter in the client registration request, if it was set, the same value will be duplicated into
an HTTP header `Cache-Control: max-age`. Afterwards, the SingleA client lookup for this header and,
if it is found, caches the received token for the specified time. Read more about caching user
tokens on the nginx side from the Lua script in the [SingleA Lua client](client/nginx.md)
description.

### Client Restriction and Signature/Ticket Validation

In the same way as in the case of
host [restriction who is allowed to make registration request](#registrant-restriction), the client
requests to the `/validate` and `/token` endpoints can be restricted by an IP address or a subnet.
To do this it is necessary to configure the `singlea.client.trusted_clients` parameter of the
SingleA bundle with a CSV formatted string with IP addresses/subnets (remember about
the `REMOTE_ADDR` value to allow the request processing from any host). After that you must add the
`is_valid_client_ip()` expression into an `allow_if` option of a `security.access_control` rule with
a corresponding path.

To activate the request signature validation and the ticket validation it is necessary to use the
`is_valid_signature()` and `is_valid_ticket()` expressions in the same way. Remember about union a
few expressions by the `and` operator:

```
- { path: ^/(validate|token), allow_if: "is_valid_signature() and is_valid_ticket() and is_valid_client_ip()" }
```

!!! caution ""

    As noted in the case of the [registrant restriction](#registrant-restriction), it is important
    to add an additional rule to prevent an invalid request processing if there is a rule that
    allows access for anonymous users. Multiple routes may be united in one rule using a regular
    expression:
    ```
    - { path: ^/(login|validate|token), roles: ROLE_NO_ACCESS }
    ```

## Sequence Diagram

``` mermaid
%%{init: {
    "sequence": { "useMaxWidth": false }
}}%%
sequenceDiagram
    actor User as User / Browser
    participant App as SingleA client / App
    participant SingleA as SingleA server
    participant Payload as Payload Endpoint

    opt App1
        User->>+App: Request to app1.domain.org
        Note over User,App: Request does not<br>contain ticket cookie
        App->>App: Check ticket cookie
        App->>-User: Redirect user to<br>the SingleA server<br>for authentication
        Note over User,App: Redirect response contains<br>URI where the user should be<br>redirected after successful login,<br>signature to protect this URI

        User->>+SingleA: Authentication request with redirect URI,<br>signature, timestamp, client id and secret
        Note over User,SingleA: Authentication process
        Note over SingleA: Make user session on the SingleA server side<br>Generate ticket value and store user attributes in a cache<br>Set ticket cookie into response headers
        SingleA->>-User: Redirect to redirect URI from authentication request

        User->>+App: Initial request (app1.domain.org)
        App->>App: Check ticket cookie
        App->>+SingleA: Validate user session
        Note over SingleA: Check user attributes existence
        SingleA-->>-App: OK 200
        App->>+SingleA: Get user token
        Note over SingleA: Compose basic JWT payload
        SingleA->>+Payload: Get additional<br>payload data
        Payload->>-SingleA: Data
        Note over SingleA: Merge payload data (with replacement)<br>Add a signature and encrypt the JWT
        SingleA-->>-App: JWT
        App->>App: Add JWT to original request
        App->>App: Process request<br>by client application
        App->>-User: Response
    end

    opt App2
        User->>+App: Request to app2.domain.org
        App->>App: Check ticket cookie
        App->>+SingleA: Validate user session
        Note over SingleA: Check user attributes existence
        SingleA-->>-App: OK 200
        App->>+SingleA: Get user token
        Note over SingleA: Compose basic JWT payload
        SingleA->>+Payload: Get additional<br>payload data
        Payload->>-SingleA: Data
        Note over SingleA: Merge payload data (with replacement)<br>Add a signature and encrypt the JWT
        SingleA-->>-App: JWT
        App->>App: Add JWT to original request
        App->>App: Process request<br>by client application
        App->>-User: Response
    end
```

## Read More

* About [tickets](bundles/singlea.md#ticket): what is it and what role does it play in SingleA.
* How to set up and use multiple user providers and authenticators
  via [realms](bundles/singlea.md#realms).
* About the [lifetime configuring](bundles/singlea.md#lifetimes-user-attributes-tickets-tokens).
* How [SingleA Security](security.md) works (and what is the problem of client registration).
* About the [SingleA client](client/concept.md).
