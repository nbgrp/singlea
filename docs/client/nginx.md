# Nginx Lua Client

## Overview

One of the most popular reverse proxy servers today is [nginx](https://nginx.org/). You can explore
a numerous benefits, which nginx provides, in the official documentation and in many articles about
this topic. The most frameworks have an examples of a nginx configuration for running web
application based on these frameworks. For example, you can see such an example in the
[Symfony documentation](https://symfony.com/doc/current/setup/web_server_configuration.html#nginx).

Nginx has a lot of useful modules, one of them
is the [lua-nginx-module](https://github.com/openresty/lua-nginx-module#readme), which makes able to
run Lua scripts directly from nginx site configuration using
the [LuaJIT](https://luajit.org/luajit.html) compiler. This module allows you to perform the
necessary checks before pass the request for further processing, modify the original request or
redirect the user to another URL. The SingleA Lua client for nginx, which is described below, is
based on this approach.

## Installation

First you need install nginx compiled
with [lua-nginx-module](https://github.com/openresty/lua-nginx-module) and LuaJIT 2.1 (or later).
Besides, you need to install the following Lua packages that the SingleA Lua client depends on:

* http
* lua-resty-http
* base64

and dependent packages for them. It is recommended to use the [LuaRocks](https://luarocks.org/) to
install this packages.

When nginx, LuaJIT and all necessary Lua packages are installed, you need to create a directory for
Lua scripts, which will contain our SingleA client (e.g. `/etc/nginx/lua`) and copy there the
file `singlea-client.lua` from
the [client repository `nbgrp/singlea-nginx-lua`](https://github.com/nbgrp/singlea-nginx-lua). In
the main nginx configuration file (`/etc/nginx/nginx.conf`), you need to add the following
directives in the `http` section (use your path to the directory with Lua scripts):

```
lua_package_path '/etc/nginx/lua/?.lua;;';
lua_shared_dict tokens 1m;
```

The `lua_shared_dict` directive defines the dictionary (the key-value storage that shared between
all nginx workers), which will be used to cache user tokens that received from the SingleA server.
It will be described in more details below, here it is only should be noted that the dictionary
name `tokens` can be any other else, and you can allocate different amount of memory for the
dictionary (not only 1 megabyte as in the example above).

## Configuration

There are 2 ways to configure the SingleA Lua client: by explicit specifying of parameters
when initialize the client, and by environment variables. The second one is especially useful when
you deploy the client application using containers and follow
the [12-Factor App](https://12factor.net/) principles. See an example of explicit configuration
[below](#examples).

!!! note ""

    To access environment variables from the Lua script in the nginx configuration context, you
    should use the [`env` directive](https://nginx.org/en/docs/ngx_core_module.html#env). It makes
    it possible to use system-wide defined environment variables or define your own, which will be
    used only by nginx workers.

Each explicit parameter of the client constructor has a paired environment variable, which has an
uppercase name with the prefix `SINGLEA_` (or with another one specified in the `env_prefix`
explicit parameter). For example, the value of the `client_id` parameter can be specified
via `SINGLEA_CLIENT_ID` environment variable. This can be helpful, if you configure requests
proxying to multiple client applications through a single nginx instance.

!!! hint ""

    You can mix both ways of specifying the client parameters. Explicit parameters have a higher
    priority.

The SingleA client parameters are listed below. The required parameters are listed at the beginning
(marked with `*`), which must be specified in any way. Parameter names will be shown according
the names of explicit parameters in the client constructor (in a lowercase and without a prefix).

* `base_url`* — scheme and domain (with a port, if necessary) of the SingleA server
  (e.g. `https://sso.domain.org:1234`).
* `client_id`* — the client identifier that was returned at registration.
* `secret`* — the client secret that was returned at registration.
* `client_base_url` — scheme and domain (with a port, if necessary) of the client application. By
  default, the variables `ngx.var.scheme` and `ngx.var.http_host` are used, but there are cases when
  you need to specify this parameter manually. For example, you can use the reverse proxy
  [Traefik](https://doc.traefik.io/traefik/), which handles request over https (port 443) and pass
  them to the nginx instance, which operates over http (port 80). In this case the `ngx.var.scheme`
  variable will be equal to `http` that is incorrect.
* `signature_key` — the private key, which used for sign requests (see
  the [Request Signature](../features/signature.md) feature description). If this parameter is
  omitted, requests will not be signed.
* `signature_md_algorithm` (default: `SHA256`) — the message digest algorithm (hash function) used
  to sign the request.
* `request_timeout` (default: `30`) — the timeout for internal requests between the SingleA client
  and the server (user session validation and user token generation).
* `ssl_not_verify` — if any non-empty value is specified, it prevents SSL certificate verification
  for internal requests between the SingleA client and the server. This can be useful in the
  development process or for testing purposes.
* `realm` — the [Realm](../bundles/singlea.md#realms) value that should be used by the SingleA
  server.
* `ticket_cookie_name` (default: `tkt`) — the name of a cookie, which should contain the
  user [ticket](../bundles/singlea.md#ticket).
* `ticket_header` (default: `X-Ticket`) — the name of an HTTP header, which should contain the
  ticket value in internal requests between the SingleA client and server.
* `token_dict` (default: `tokens`) — the name of the shared dictionary where the received user token
  will be cached by the SingleA client.

??? info "Shared dictionary for user tokens caching"

    The shared dictionary is created by the `lua_shared_dict` nginx directive, which was mentioned
    above in the [Installation](#installation) section. It creates the key-value in-memory storage
    shared between all nginx workers, which size is limited by the specified in the directive value.
    When the SingleA client receive the user token, it stores this token in the storage (cache) for
    the time specified in the `Cache-Control: max-age` HTTP header (if it exists in the response
    with the token, otherwise the token is not cached). Until the cache item will expire (or will be
    flushed, see `token_flush_header` parameter description below), the token value will be fetched
    from the cache, and not requested from the SingleA server.

    When the nginx daemon is restarted, the cache storage is cleared. See the
    [`lua_shared_dict`](https://github.com/openresty/lua-nginx-module#lua_shared_dict) directive
    description for more details.

* `token_header` (default: `Authorization`) — the name of an HTTP header of an original request,
  which should be written by the SingleA client and should contain the user token that received from
  the SingleA server or from the cache (shared dictionary).
* `token_prefix` (default: `Bearer `, with a space in the ending) — the prefix, which should be
  added to the token header before the user token value.
* `token_flush_header` (default: `X-Flush-Token`) — the name of an HTTP header, which used in 2
  cases:
    * You can flush user token using the `flush_token()` client method **after** the request
      processing. This method looks for the flush header in the response, which was generated by the
      client application (see the second example [below](#examples)).
    * You can enforce the client to remove cached token value in the `token()` client method with
      subsequent receiving of the fresh user token and adding it to the original request.
* `client_id_query_parameter` (default: `client_id`) — the name of the GET parameter with the client
  identifier value.
* `secret_query_parameter` (default: `secret`) — the name of the GET parameter with the client
  secret.
* `realm_query_parameter` (default: `realm`) — the name of the GET parameter with the realm value.
* `signature_query_parameter` (default: `sg`) — the name of the GET parameter with the request
  signature.
* `timestamp_query_parameter` (default: `ts`) — the name of the GET parameter with the timestamp
  when the request was created.
* `redirect_uri_query_parameter` (default: `redirect_uri`) — the name of the GET parameter with the
  URI to which the user should be redirected after a successful operation on the SingleA server side
  (used for user login and logout).
* `login_path`, `logout_path`, `validate_path`, `token_path` (defaults: `/login`, `/logout`,
  `/validate` and `/token`) — relative paths of the methods on the SingleA server side for user
  login, logout, the user session validation, and user token generation.

!!! important ""

    Parameters `*_query_param` and `*_path` make it possible to customize the GET parameters and
    the routes in case the default values cannot be used. On the server side there is an ability to
    change names of the GET parameters. The paths of the routes can be changed via standard way of
    the routes customization (using the `config/routes.yaml` and `config/routes/*.yaml` files).

!!! caution ""

## Usage

The SingleA Lua client has the following methods.

- `login()` — check that the ticket cookie exists and validate the user session (on the SingleA
  server side). If there is no cookie or the user session is invalid, redirect the user to login on
  the SingleA server and specify the current request URI as the `redirect_uri` parameter value.
- `logout()` — if the request contains the ticket cookie, redirect the user to logout on the SingleA
  server side with specifying the current request URI as the `redirect_uri` parameter value.
- `validate()` — if the request contains the ticket cookie, validate the user session (on the
  SingleA server side). Returns an HTTP error **Unauthorized 401** if the session is invalid or the
  request does not contain the ticket cookie.
- `token(auth_required=true)` — send an HTTP request to the SingleA server to receive an
  authenticated user token. The decision to check the user's session or not is up to the client
  application developer (see the first example [below](#examples), where the `login()` method
  precedes the `token()` method). The `token()` method will return an HTTP error **Unauthorized
  401** if the user is not authorized. It is possible to call this method with optional boolean
  argument `auth_required` set to `false`. In this case, if the user is not authorized, the error
  will not be returned and the request will continue processing (without an HTTP header with the
  user token, of course).

All these methods, excepts `logout()`, return the client instance, which allows using of method
chaining.

### Examples

Below are few examples of using the SingleA Lua client.

**1. Login if necessary, request user token and add it into an original request**

!!! note ""

    Pay attention on explicit parameters specified for the `new` method (the client constructor).

```
server {
  location ~ ^/any$ {
    rewrite_by_lua_block {
       require("singlea-client").new {
          client_id = "hard_coded_client_id",
          request_timeout = 10,
          token_header = "Custom-Authorization",
       }
          :login()
          :token()
    }
    # ...
  }
}
```

**2. Request user token if authenticated only and flush the token if required (via flush header)**

```
server {
  location ~ ^/any$ {
    rewrite_by_lua_block {
       require("singlea-client").new()
          :token(false)
    }

    # Some request processing, e.g. with FastCGI
    # ...

    header_filter_by_lua_block {
       require("singlea-client").new()
          :flush_token()
    }
  }
}
```

**3. Validate user session only**

```
server {
  location ~ ^/any$ {
    rewrite_by_lua_block {
       require("singlea-client").new()
          :validate()
    }
    # ...
  }
}
```

!!! hint ""

    Use `"/n"` as a newline when pass a signature key as an explicit constructor parameter or an
    environment variable.

### Self Payload Service

If you are going to use the client application as an external service for
the [Payload Fetcher](../features/payload-fetcher.md) feature, do not forget to exclude such
requests from processing by the client (because these requests do not contain user ticket cookie).

In the following example assumed that the SingleA server sends Payload Fetcher requests to
the `/_payload` path on the client application domain:

```
location ~ ^/any$ {
  rewrite_by_lua_block {
     if ngx.var.request_uri:sub(1, 9) == '/_payload' then
        return
     end

     require("singlea-client").new()
        :login()
        :token()
  }
  # ...
```

### CORS and `OPTIONS` Requests

If you need to process `OPTIONS` requests, you can handle such requests directly in the Lua code:

```
location ~ ^/any$ {
  rewrite_by_lua_block {
     if ngx.var.request_method == 'OPTIONS' then
        ngx.header['Access-Control-Allow-Origin'] = 'static.domain.org'
        ngx.header['Access-Control-Allow-Methods'] = 'HEAD, GET, POST, PUT, PATCH, DELETE'
        ngx.header['Access-Control-Allow-Headers'] = 'Content-Type, Accept, Cache-Control, X-Requested-With'
        ngx.header['Content-Type'] = 'text/plain; charset=utf-8'
        ngx.header['Content-Length'] = 0
        ngx.exit(ngx.HTTP_NO_CONTENT)
     end

     require("singlea-client").new()
        :login()
        :token()
  }
  # ...
```
