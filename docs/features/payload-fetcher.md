# Payload Fetcher

In the description of the [Tokenization](tokenization.md) feature was mentioned, that the tokenizer
takes as an argument an array with user data, but it was not considered how this array is composed.
The fact is that it is composed in 2 phases:

* firstly it is composing of the [user attributes](../bundles/singlea.md#user-attributes) according
  the user claims, specified in the client tokenization feature config;
* then it is merging with data, received with help of the Payload Fetcher feature, which is
  considered closer below.

This feature makes it possible to make an HTTP request to an external service to receive an
additional user data, which should then be merged to the token payload. This request will contain a
set of user data similar to the payload for a user token. Main idea of the feature is that the
client application may need some data as part of the token payload, which is based on business logic
that is unknown on the SingleA server side.

The most popular example is a mapping of user attributes to the user roles on the client application
side. Suppose that user attributes contain user groups from Active Directory (which may be used as
an Identity Provider). You can create a web service, which will take a request with user groups list
and return a list of user roles in the client application, based on these groups. With help of the
Payload Fetcher, the SingleA server makes an HTTP request to your web service and will merge
received data to the token payload. It is worth to be noted, that this HTTP request is made only on
the token generation, and with help of the [token TTL](tokenization.md#token-ttl) the SingleA server
will prevent excessive load on your web service. This approach is an alternative for a mapping user
groups to the roles on each request on the client application side.

The SingleA project have 2 implementations of the Payload Fetcher feature.

* [JSON Fetcher](../bundles/json-fetcher.md) — send user data as a JSON to an external service
  endpoint and expects to receive a JSON as a response, which must be merged to the token payload.
  Use this implementation of Payload Fetcher in a secured network only, where is impossible data
  interception by attackers during transmission over the network.

* [JWT Fetcher](../bundles/jwt-fetcher.md) — send user data as JWT payload. The JWT has a mandatory
  signature and (optionally) can be encrypted. It is recommended to use this implementation in any
  case, when you are not absolutely sure about your network safety and data transmission between the
  SingleA server and the external service can be intercepted. Read more about this in
  the [SingleA Security](../security.md) section.
