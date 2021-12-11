# Tokenization

In most cases, it is not enough to just authenticate the user. It is necessary to get the user
properties: name, email(s), roles, etc. To solve this issue the SingleA project have the
Tokenization feature, which makes it possible to generate user tokens.

User token is a unique string that provides access to user data for a client application. Perhaps,
the most popular example of a token can be considered a JWT
([RFC 7519](https://tools.ietf.org/html/rfc7519): JSON Web Token). In this format user data encoded
directly in a token content. An implementation of the Tokenization feature based on a JWT format
included in the SingleA project, you can read more about it in the [JWT Bundle](../bundles/jwt.md)
section.

JWT is not the only way to transmit user data to the client application. You can create your own
implementation of this feature. For example, it can store user data in some storage, shared by the
SingleA server and the client application, and use as a token value the key that is used to fetch
stored data. Whatever the logic of token generation, you must implement
`SingleA\Contracts\Tokenization\TokenizerInterface` and particularly
the `TokenizerInterface::tokenize` method, which take as arguments the user identifier, a payload
(an array with user data) and client feature config (which contains at least a token TTL and
[user attributes](../bundles/singlea.md#user-attributes) names for the token). As a
result `tokenize` must return a string that will be used as a user token.

Regardless of the tokenizer implementation, a response with the token will contain an HTTP header
`Cache-Control: max-age` with a value according the token lifetime from the client feature config
(if it was specified on registration). This makes it possible to cache the token and avoid excessive
load on the SingleA server.

## Token TTL

Specify as a token lifetime a sufficiently small value on the client registration according to
business logic of your application. THe default value is 10 minutes.

SingleA provide the `user:logout <identifier>` command that allow you to log the user out forcibly,
so use the minimal possible value as a token TTL is not always justified. But if user data can be
changed between token generation requests (not only on successful authentication; see
`SingleA\Bundles\Singlea\Event\UserAttributesEvent` [description](../bundles/singlea.md#userattributesevent)
in the SingleA Bundles section), it makes sense to specify small value, otherwise the client
application will work with outdated data for a long time.
