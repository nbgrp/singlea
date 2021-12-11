# SingleA JWT Bundle

[![Latest Stable Version](http://poser.pugx.org/nbgrp/singlea-jwt-bundle/v)](https://packagist.org/packages/nbgrp/singlea-jwt-bundle)
[![Latest Unstable Version](http://poser.pugx.org/nbgrp/singlea-jwt-bundle/v/unstable)](https://packagist.org/packages/nbgrp/singlea-jwt-bundle)
[![Total Downloads](http://poser.pugx.org/nbgrp/singlea-jwt-bundle/downloads)](https://packagist.org/packages/nbgrp/singlea-jwt-bundle)
[![License](http://poser.pugx.org/nbgrp/singlea-jwt-bundle/license)](https://packagist.org/packages/nbgrp/singlea-jwt-bundle)

[![ko-fi](https://ko-fi.com/img/githubbutton_sm.svg)](https://ko-fi.com/S6S073WSW)

## Application register options

- `signature_algorithm` (required) — the signature algorithm name which used to sign generated token
  (e.g. for JWT implementation it can be one of the following algorithms:
  "ES256", "ES384", "ES512", "RS256", "RS384", "RS512", "HS256", "HS384", "HS512").
- `private_key_bits` — how many bits should be used to generate a private key which will be used for
  token signature.
- `private_key_curve_name` — what curve name should be used to generate a private key (if
  "ES" prefixed algorithm specified in `signature_algorithm` option).
- `ttl` — the token TTL (time to live) in seconds.
- `claims` — the list of the user attributes (properties) names that SingleA should include in the
  generated token payload. These attributes will be taken from the general user payload with help of
  `SingleA\Contracts\Tokenization\PayloadManagerInterface` implementation (for example, from the
  [nbgrp/singlea-payload](https://github.com/nbgrp/singlea-payload) bundle). If the claim ends with
  `[]`, payload will contain the user attribute value as an array (even if was not an array
  initially).
- `payload_endpoint` — contains the URI which respond for the request with extra payload data.<br>
  The request method and content depend on
  `SingleA\Contracts\Tokenization\PayloadFetcherInterface` implementation. For
  example, the [nbgrp/singlea-payload](https://github.com/nbgrp/singlea-payload) bundle makes
  `POST` request providing a JSON with an array which contained:
  - user identifier;
  - user groups (or an empty array if the user does not implement the
    `SingleA\Contracts\User\GroupsAwareInterface`);
  - complete user payload just only without extra data.
- `payload_endpoint_options` — an array with HTTP Client custom settings. It depends on the
  `SingleA\Contracts\Tokenization\PayloadFetcherInterface` implementation and what HTTP Client it
  uses.
