# Payload Fetcher

In the description of the [Tokenization](tokenization.md) feature was mentioned, that the tokenizer
takes as an argument an array with user data, but it was not considered how this array is composed.
The fact is that it is composed in 2 phases:

* first, it is composing of the [user attributes](../bundles/singlea.md#user-attributes) according
  the user claims, specified in the client tokenization feature config;
* then, it is merging with the data, received using the Payload Fetcher feature, which is
  considered closer below.

This feature makes it possible to send an HTTP request to an external service to receive additional
user data, which should then be merged with the token payload. This request will contain a
set of user data similar to the payload for the user token. The main idea of the feature is that the
client application may need some data as a part of the token payload, which is based on the business
logic that is unknown on the SingleA server side.

The most popular example is a mapping of user attributes to the user roles on the client application
side. Suppose that the user attributes contain the user groups from Active Directory (which may
be used as an Identity Provider). You can create the web service, which will take a request with the
user groups list and return the list of user roles in the client application, based on these groups.
Using the Payload Fetcher, the SingleA server sends an HTTP request to your web service and will
merge received data to the token payload. It is worth to be noted, that this HTTP request is made
only on the token generation, and with help of the [token TTL](tokenization.md#token-ttl) the
SingleA server will prevent excessive load on your web service. This approach is an alternative for
a mapping the user groups to the roles on each request on the client application side.

The SingleA project have 2 implementations of the Payload Fetcher feature.

* [JSON Fetcher](../bundles/json-fetcher.md) — send user data as a JSON to an external service
  endpoint and expects to receive a JSON as a response, which must be merged with the token payload.
  Use this implementation of Payload Fetcher in the secured network only, where it is impossible for
  attackers to intercept the data during transmission over the network.

* [JWT Fetcher](../bundles/jwt-fetcher.md) — send user data as JWT payload. The JWT has a mandatory
  signature and can be encrypted (optionally). It is recommended to use this implementation in any
  case, when you are not absolutely sure about your network safety and data transmission between the
  SingleA server and the external service can be intercepted. Read more about this in
  the [SingleA Security](../security.md) section.
