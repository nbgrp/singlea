# Request Signature

When processing requests, it is important to be sure that they were sent from trusted source and
were not forged during network transmission. This is especially true for login and logout requests,
which include the GET parameter that points to the URI where the user must be redirected after
successful authentication or logging out.

An attack with valid requests that do not depend on request sent time, which leads to reduce of
SingleA performance, is also a danger.

!!! caution ""

    The Request Signature feature do not provide complete solution to protect the SingleA server
    from DoS attacks.

To solve this issues the SingleA Bundle contains the Request Signature feature, which allows passing
in a user/client request the following GET parameters:

* digital **signature** of remaining GET parameters of this request;
* **timestamp** of the beginning of this request.

The SingleA server checks whether the request has not expired and whether the signature is valid
(according remaining GET parameters; you can exclude some additional parameters from validation with
help of the `singlea.signature.extra_exclude_query_parameters` parameter).

!!! caution ""

    To use this feature you must use the `is_valid_signature` function in
    [security expression](../bundles/singlea.md#access-control-expression) for an `allow_if` option
    of a `security.access_control` record.

!!! note ""

    SingleA does not use [Symfony Rate Limiter](https://symfony.com/doc/current/rate_limiter.html),
    but you can do it. Easiest way to use Rate Limiter with SingleA controllers is to create an
    event subscriber for the event `Symfony\Component\HttpKernel\Event\ControllerEvent` and
    [add Rate Limiter usage](https://symfony.com/doc/current/event_dispatcher/before_after_filters.html#creating-an-event-subscriber)
    for the necessary controllers.
