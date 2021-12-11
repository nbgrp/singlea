# SingleA

[![Latest Stable Version](http://poser.pugx.org/nbgrp/singlea/v)](https://packagist.org/packages/nbgrp/singlea)
[![Latest Unstable Version](http://poser.pugx.org/nbgrp/singlea/v/unstable)](https://packagist.org/packages/nbgrp/singlea)
[![Total Downloads](http://poser.pugx.org/nbgrp/singlea/downloads)](https://packagist.org/packages/nbgrp/singlea)
[![License](http://poser.pugx.org/nbgrp/singlea/license)](https://packagist.org/packages/nbgrp/singlea)
[![Gitter](https://badges.gitter.im/nbgrp/singlea.svg)](https://gitter.im/nbgrp/singlea)

[![PHP Version Require](http://poser.pugx.org/nbgrp/singlea/require/php)](https://packagist.org/packages/nbgrp/singlea)
[![Codecov](https://codecov.io/gh/nbgrp/singlea/branch/main/graph/badge.svg?token=P2KZ9A14R4)](https://codecov.io/gh/nbgrp/singlea)
[![Audit](https://github.com/nbgrp/singlea/actions/workflows/audit.yml/badge.svg)](https://github.com/nbgrp/singlea/actions/workflows/audit.yml)

[![SymfonyInsight](https://insight.symfony.com/projects/dbfe1659-ab90-48df-b5eb-97faaaf7c055/small.svg)](https://insight.symfony.com/projects/ed7b9263-179c-442a-9f45-6877e4e6dbdb)

## Overview

This repository provides a skeleton to create your own SingleA instance — SingleAuth protocol based
authentication service.

## Installation

To install your own SingleA you need to download [the latest release](https://github.com/nbgrp/singlea-skeleton/releases/latest)
of this repository (or fork it) and perform the following steps.

### 1. Install dependencies

First, run `composer install --no-dev` in `app` directory. It will install all necessary dependencies.

Do not be surprised that Symfony Framework Bundle installs without running recipes and adding
standard configuration files and environment variables. This due to skeleton contains preconfigured
settings (in `app/config` directory) and committed `app/symfony.lock` file.

### 2. Add necessary packages

Run `composer require <package>` command in `app` directory to add them into your SingleA instance.

SingleA has feature-modular structure based on the Dependency Inversion Principle using contracts.
What does it mean?

To make SingleA flexible and customizable, interfaces of all interchangeable parts of the service
were extracted into separated [SingleA contracts](https://github.com/nbgrp/singlea-contracts) package
and implemented also in separated pluggable bundles.

You can add the following default SingleA contracted bundles:
- [nbgrp/singlea-redis](https://github.com/nbgrp/singlea-redis-bundle) — implements storage of the service
entities in a [Redis](https://redis.io/) and provides customized session handler.
- [nbgrp/singlea-jwt](https://github.com/nbgrp/singlea-jwt-bundle) — implements the tokens generation as
JWTs (JSON Web Tokens).
- [nbgrp/singlea-payload](https://github.com/nbgrp/singlea-payload) — implements tokens payload
management, including fetching extra payload from external URI.

Besides the SingleA contracted bundles, you can add any other packages that you need. For example,
you might need to add some external authentication bundles like:
- [nbgrp/onelogin-saml-bundle](https://github.com/nbgrp/onelogin-saml-bundle) for SAML authentication;
- [symfony/monolog-bundle](https://github.com/symfony/monolog-bundle) for using Monolog library;
- [getsentry/sentry-symfony](https://github.com/getsentry/sentry-symfony) for Sentry integration;
- etc.

When installing packages, you should keep in mind that SingleA uses PHP configs. To make able using
of YAML or XML configs, you need to change Kernel class in `app/src/Kernel.php` yourself,
specifically the `configureContainer` method.

## Configuration

Since SingleA is a Symfony based application, it uses environment variables to configuration. You
can find all significant environment variables with their default values in `app/.env`. To override
any variable make an `app/.env.local` file instead of change values in `app/.env`.<br>
First, you should override the `APP_SECRET` environment variable with any random string value.

If you are going to use SingleA as containerized application, you can pass env values in runtime.
You can find an example of using SingleA with docker in [example repository](https://github.com/nbgrp/singlea-example).

### Routes

SingleA routes configured in `app/config/routes/singlea.php`. You are free to change their names and
paths as you wish.

### Security

This skeleton provides preconfigured security configuration `app/config/packages/security.php` with
inlined in-memory test user that **SHOULD** be removed before production service usage.

Pay attention that `main` firewall initially configured to use in-memory user provider and
`http_basic` authenticator. You need to configure your own user provider and use it to configure
the firewall, and also you need to configure authenticator.

In addition, you can use multiple firewalls to implement [Multiple IdP](#multiple-idp) feature.

#### Access Control

There are a few things to watch in access control setting that you should pay attention.

1. Application register controller (`/register`) allows public (unauthorized) access, but only for
clients from networks specified in `REGISTER_TRUSTED_IP` environment variable. It can contain comma
separated list of IP addresses or networks (e.g. `127.0.0.1,172.16.0.0/12`).

2. Initially register controller requires HTTPS protocol. If you wish you can remove this
requirement.

3. Validate (`/validate`) and token (`/token`) controllers configured as public accessible, but actually
they require user authorization in other way.

4. With help of the `singlea.authentication.requires_channel` parameter you can specify which
protocol must be used for authentication controller (`/login`). This parameter initialized with
value of the `AUTH_REQUIRES_CHANNEL` environment variable and might be completely removed from
`app/config/parameters.php` if you do not want to check auth controller protocol requirement.<br>
Also, in case of multiple firewall configuration, you can set extra parameters to configure auth
controller protocol requirement per firewall. To do this you should set a parameter with name like
`singlea.authentication.requires_channel.<firewall_name>` and required protocol as a value.

### Trusted Host

With help of environment variable `TRUSTED_HOSTS` SingleA prevents HTTP Host header attacks.
You need to define this variable with regular expression that will match your SingleA instance
domain (like `^sso\.domain\.org$`).

### Work behind Load Balancer or a Reverse Proxy

According to the [Symfony documentation](https://symfony.com/doc/current/deployment/proxies.html),
SingleA uses the `TRUSTED_PROXIES` environment variable (default `127.0.0.1`) to configure the
trusted proxies IPs. This is necessary for correct detection of the request protocol. To disable
this feature, just remove `trusted_proxies` and `trusted_headers` settings from
`app/config/packages/framework.php`.

### Session

Session preconfigured in `app/config/packages/framework.php`. Especially you should pay attention
to values of the following environment variables:

- `SESSION_COOKIE_DOMAIN` (default ".local") — **SHOULD** determine a domain (prefixed with a dot),
that is common to all applications using this SingleA instance .
- `SESSION_COOKIE_NAME` (default "singlea").
- `SESSION_TTL` (default 86400).

If you are going to use some kind of custom session handler, you should not forget to provide a
`framework.session.handler_id` parameter.

### Features settings

Here listed a few environment variables that need for purpose of some basic and advanced usage.
For more details look at an according section.

- `APPLICATION_QUERY_PARAMETER` (default "app_id") — determines the query parameter name that
contains an application ID (which is required to authenticate user, verify user's session and
retrieve user's token).
- `REGISTER_APPLICATION_HEADER` (default "Application-Id") — determines the HTTP header name
contains registered application ID (see [Application Register](#application-register)).
- `REGISTER_AUTH_SIGNATURE_ALGORITHM` (default "SHA256") — default signature algorithm used for
verification of `redirect_uri` authentication parameter for applications registered with
`app:register` command (see [Register Application (CLI)](#application-register-cli)).
- `DEFAULT_IDP` (default "main") — default firewall when no other IdP (firewall) provided into
request (see [Multiple IdP](#multiple-idp)).
- `IDP_QUERY_PARAMETER` (default "idp") — determines IdP (firewall) query parameter name (see
[Multiple IdP](#multiple-idp)).

## Basic usage

Requests for user authentication, user session verification and user token retrieval require the
application ID to be provided in the query parameter defined in the `APPLICATION_QUERY_PARAMETER`
environment variable.

### Application Register

To be able to use SingleA, client applications must be previously registered according to their
needs. Each application can use SingleA to authenticate user, verify user's session and retrieve
user's token.

To enable application to request user authentication or retrieve user's token, it must be registered
with submitting appropriate options provided as JSON:
``` json
{
  "auth":  { ... }, // user authentication options
  "token": { ... }  // user token retrieval (tokenizer) options
}
```

Every root key options handled by separated config factory that implements an appropriate interface:
- `SingleA\Contracts\Authentication\AuthenticationConfigFactoryInterface` for authentication config;
- `SingleA\Contracts\Tokenization\TokenizerConfigFactoryInterface` for tokenizer config.

For this reason it is possible to implement your own feature config creation logic if you wish.<br>
See [SingleA Extending](#singlea-extending) section for more details.

Response for the register request will contain the generated application ID in the HTTP header
defined by `REGISTER_APPLICATION_HEADER` environment variable (`Application-Id` by default) and
the public key for user token validation as the response body (if tokenizer option
`signature_algorithm` was provided).

#### User Authentication Options

- `signature_public_key` (required) — contains the public key that will be used to validate the `redirect_uri`
parameter from the user authentication request. **Notice**: line breaks should be replaced by `\n`
escape sequence.
- `signature_algorithm` (required) — contains the name of the OpenSSL signature algorithm without
"OPENSSL_ALGO_" prefix (see [Signature Algorithms](https://www.php.net/manual/en/openssl.signature-algos.php)
list). It is recommended to use the "SHA256" algorithm or better.

#### Tokenizer Options

> The SingleA skeleton does not contain `TokenizerConfigFactoryInterface` implementation.
>
> If you are using the [nbgrp/singlea-jwt-bundle](https://github.com/nbgrp/singlea-jwt-bundle) bundle, see its README
> to explore application register options.

### User Authentication

> You need to customize the method
> `SingleA\Authentication\EventListener\SuccessfulLoginListener::setPayload` to add some real
> payload data for an authenticated user. If you do not do this, the user token payload will not
> contain any data.

The user authentication request must contain the following query parameters.
- `app_id` (or other defined by `APPLICATION_QUERY_PARAMETER` environment variable) — an application
ID from the application register response.<br>
**Notice**: The application should be granted to authenticate the user (registered with the
provision of `auth` options).
- `redirect_uri` — a URI where the user should be redirected after successful authentication.
- `signature` — a digital signature for the `redirect_uri` value that generated on the application
side with an algorithm specified into the `auth.signature_algorithm` application registration option
and the private key which is paired with one from the `auth.signature_public_key` application
registration option. The signature should be url-encoded because originally it is a binary string.

### User's Session Verification

- should contain payload

### User's Token Retrieval

- should contain payload
- application should be granted to retrieve user token

## Advanced

### Multiple IdP

### CLI Application Management

#### Application Register (CLI)

#### Application Info

#### Remove Application

#### Purge Outdated Applications

## SingleA Extending

