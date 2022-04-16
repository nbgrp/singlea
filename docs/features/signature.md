# Request Signature

When processing requests, it is important to be sure that they were sent from the trusted source and
were not forged during the network transmission. This is especially true for login and logout
requests, which include the GET parameter that points to the URI where the user must be redirected
after successful authentication or logout.

An attack with valid requests, which do not depend on the request sent time, is also a danger. It
leads to a reduced SingleA performance.

!!! caution ""

    The Request Signature feature do not provide complete solution to protect the SingleA server
    from DoS attacks.

To solve this issues the SingleA bundle contains the Request Signature feature, which allows the
user or client to pass in a request the following GET parameters:

* digital **signature** of remaining GET parameters of the request;
* **timestamp** of the beginning of the request.

The SingleA server checks whether the request has not expired and whether the signature is valid
(according remaining GET parameters; you can exclude some additional parameters from validation
using the `singlea.signature.extra_exclude_query_parameters` parameter).

When the client application register, it is possible to specify the `signature.skew` parameter,
which should contain a number (positive or negative) of seconds that will be added to
the `timestamp` value from the request before comparison with the server time. This is helpful when
the client and the server work in different timezones. For example, if the server works in the
**UTC-4** timezone and the client in the **UTC+2** timezone (difference is six hours), the client
registration request can include the `signature.skew` parameter with `-21600` as a value
(the server time minus the client time).

It should be noted, that the server time for comparison is given at the moment when the request was
come to the processing, not when it is actually being processed. This allows you not to worry about
the duration of the user's interactive login.

!!! caution ""

    To use this feature you must use the `is_valid_signature()`
    [security expression](../bundles/singlea.md#access-control-expression) in an `allow_if` option
    of a `security.access_control` rule.

!!! note ""

    SingleA does not use the
    [Symfony Rate Limiter](https://symfony.com/doc/current/rate_limiter.html) component, but you can
    do it. Easiest way to use the Rate Limiter with the SingleA controllers is to create an event
    subscriber for the event `Symfony\Component\HttpKernel\Event\ControllerEvent` and
    [add Rate Limiter usage](https://symfony.com/doc/current/event_dispatcher/before_after_filters.html#creating-an-event-subscriber)
    for the necessary controllers.
