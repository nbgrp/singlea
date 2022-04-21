# SingleA Security

## Strengths

At the beginning, let's consider the case when we get the strongest protection against attacks to
user data (attempts to compromise or forge them) or forge the request to the SingleA server from a
user or client. For this assume, that the client was registered with the following features enabled:

* [Request Signature](features/signature.md)
* [JWT Tokenizer](bundles/jwt.md)
* [JWT Payload Feature](bundles/jwt-fetcher.md)

To get the required level of protection, the JWT Tokenizer and JWT Payload Fetcher features
configurations should include settings for both the signature encryption of data (for the JWT
Payload Fetcher feature this applies to both the request and response configuration). Finally, will
assume that on the SingleA server side are used rotatable keys for both client feature configs
encryption and user attributes encryption.

Let's take a closer look at these assumptions.

### Request Signature

Thanks to the Request Signature feature, you can be sure about 2 things:

* GET parameters of the request was not forged during transmission;
* the time elapsed between sending the request and receiving it by the server does not exceed the
  configured limit.

!!! hint ""

    In addition to the Request Signature feature you can use the Symfony Rate Limiter component. See
    the note about it in the [feature description](features/signature.md).

### JWT Payload Fetcher

Thanks to the signature and encryption of the transmitted data, both from the SingleA server to an
external service, which produces an additional user token payload, and vice versa, user data
compromise and forgery becomes extremely uneasy. Using reliable algorithms for signing and
encrypting data reduces the chance of a successful attack.

### JWT Tokenizer

Similar to the [JWT Payload Fetcher](#jwt-payload-fetcher), JWT data signing and encrypting helps to
avoid user data compromise and forgery, especially when combined with reliable algorithms.

Besides, you can use your own implementation of the [Tokenization](features/tokenization.md) feature
that (for example) use a third-party storage, where the SingleA server puts user data, and the
client reads it (instead of the direct user data transmission). In this way, an attacker would not
be able to extract from the obtained token any useful information.

### Client Feature Configs Encryption

Even if an attacker can steal client feature configs from the storage, he will not be able to
extract any information from them, since they are persisted in encrypted form. The following
components are used to decrypt the configuration.

* The client secret provided in every request between the client application (or the user) and the
  SingleA server. The secret is known to the client only.
* [Rotatable keys](bundles/singlea.md#sodium-encryption-keys), which should be stored in a safe
  place and passed to the SingleA instance dynamically. It is recommended to use SingleA from a
  container and provide the keys (as any other configuration) via environment variables.

!!! caution ""

    Do not keep the keys in your SingleA instance code base (e.g. in `.env` file). Such approach
    significantly simplifies the task of compromising the feature configs for attackers.

Only when both components are present, the contents of the feature configs can be decrypted. And,
even if the keys and the secret of one client were stolen, which lead to compromising of this
client's feature configs, the configs of other clients are still protected by their secrets.

!!! caution ""

    To avoid compromising client secrets, it is recommended not to write GET parameters to the web
    server logs, or to store them in a safe place.

### User Attributes Encryption

The protection of user attributes is performed in the same way as in the case
of [client feature configs](#client-feature-configs-encryption): they are encrypted using the
following 2 components.

* The user [ticket](bundles/singlea.md#ticket), which provided via an HTTP header (to prevent it
  from been written to the web server logs). The ticket value is extracted by the SingleA client
  from cookies of the user request to the client application, which originally set by the SingleA
  server when the user was successfully authenticated.
* [Rotatable keys](bundles/singlea.md#sodium-encryption-keys), as in the case of client feature
  configs.

If the keys and the ticket of some user were stolen, this does not compromise other users
attributes.

## Weakness

There is no typo in the title above. As you may have noticed, if you use all the available SingleA
features, you gеt an authentication service and a user token generation service that are totally
protected of the Man-in-the-middle (MITM) attacks. And there is only one point in which SingleA
cannot guarantee protection for you: client registration.

The matter is that even if you are using the HTTPS protocol, when sending a request through an
unsafe network (like the Internet) you cannot be absolutely sure that the request will not be
intercepted and forged by someone, who owns the root certificates (and encryption keys). It seems
like paranoia, but there are countries where it is quite real. The same is true for registration
responses.

Unfortunately, there is no common solution for this issue. But on the other side the team of the
SingleA project proceeds from the fact that the risk of using such heavy methods of attack against
the SingleA instance is only possible with respect to serious offenders, who are doing terrible
things. Who are doing such things cannot pretend to protection of SingleA. No way.

!!! important "SingleA was made for kind people against the evil."

In other cases, to make the registration process safer there are 2 helpful expression language
functions, which can be used in an `allow_if` option of a `security.access_control` rule:
`is_valid_registration_ip()` and `is_valid_registration_ticket()`. Read more about them in
the [Access Control Expression](bundles/singlea.md#access-control-expression) section of the SingleA
bundle description.

Besides, it is possible to register a new client using the `client:register` command that takes JSON
with client's registration data as an argument. It can be useful in a case, when the registration
via an HTTP request cannot be performed for some reason (e.g. for security reasons).

## Security Advices

### HTTPS

The use of HTTPS protocol is not mandatory, but it is strongly recommended. The transmission of
encrypted data over an unsecured protocol simplifies intercept of data and raises the risk of their
compromise.

!!! caution ""

    If you have configured the SingleA instance with the `singlea.ticket.samesite` parameter equals
    `true` (see the [SingleA bundle configuration](bundles/singlea.md#configuration) for more
    details), the ticket cookie will forcibly have the `Secure` attribute and client applications
    will should work over HTTPS (to be able read the cookie value).

### JWE

!!! note ""

    This concerns the [JWT](bundles/jwt.md) and [JWT Fetcher](bundles/jwt-fetcher.md) bundles.

If user tokens contain sensitive user data, always use the encryption (see the JWE parameters in the
registration parameters in the bundles' description). Do not rely on the HTTPS protocol and a safe
communication channel only. Countless user data leaks is a result of the recklessness of those who
should have taken care of their protection.

!!! important "Acknowledgements"

    Florent Morselli (also known as [Spomky](https://github.com/Spomky)) is a creator of the
    [`web-token/jwt-framework`](https://github.com/web-token/jwt-framework) package. He has done a
    great job, thanks to that we can create more reliable applications. Let's take advantage of this
    opportunity!

### Strong Algorithms

Use strong algorithms to sign and encrypt data whenever possible.

* SHA256/384/512 for Request Signature.
* ES256/384/512 to sign JWT (JWS parameters).
* ECDH-ES (and derived) to encrypt JWT keys (JWE parameters).
* A128GCM/192/256 to encrypt JWT content (JWE parameters).

In the future these recommendations might be changed as more reliable algorithms become available.

### Keys Rotation

Do not ignore an ability to rotate the keys used for encryption of client feature configs and user
attributes. Automate this process if possible, but if this is impossible, do it manually
periodically. This is significantly increase your SingleA instance reliability.

#### Client Feature Configs

The best solution is to organize a regular re-deploy of the client applications with a certain
period (or more often): once a day/week/month or something else. In this case you can rotate the
keys for encryption of client feature configs with the chosen period (automatically or manually).

If it is not possible to organize a regular re-deploy of the client applications, you can record the
generation date for each key and periodically run the `client:oldest` command, which returns
information about the oldest registered client. Among other things, the information contains the
client creation date, so the keys older than that date could be removed safely.

#### User Attributes

If the SingleA configuration parameter `singlea.authentication.sticky_session` is equals `true`, you
can rotate the keys for encryption of the user attributes periodically. Do this with the period
equal to the lifetime of the cache item that keeps the user attributes (read about it in
the [Cache Pool Management](bundles/singlea.md#cache-pool-management) section of the SingleA bundle
description). Otherwise, if your user session may last an unexpected period of time, you should
think about your own strategy for rotating these keys.
