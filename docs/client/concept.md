# SingleA Client Concept

SingleA solves the following 3 issues:

* authenticate the user;
* validate user session;
* generate [user token](../features/tokenization.md).

The easiest way to integrate your client application with the SingleA server and use all these
abilities is to use the SingleA client. It allows you to do not spend your time on finding out with
details of the [SingleAuth](../singleauth.md) framework and the SingleA server implementation.

The SingleA client should be can solve the following use cases:

* deny access (whole or partly) to the client application for unauthenticated users and redirect
  them to the SingleA server for authentication if it is necessary, with subsequent redirect them
  back to the application;
* receive from the SingleA server [user token](../features/tokenization.md) and add it to the
  original request (for a further processing on the application side);

The correct configuration of the SingleA client should allow you to do not worry about an
integration with the SingleA server and be sure that, if the request contains user token, so the
user is authenticated, and you can get user data from the token or with help of this token.

!!! note ""

    It is worth to be noted that user authentication is not mandatory for using the client
    application. The SingleA client should provide an ability to use it for anonymous users if you
    want it.

The ability to make a fine tune the SingleA client, which takes into account all the SingleA server
capabilities, is important part of the client implementation. At present there are the following
implementations of the SingleA client:

* [nginx Lua client](nginx.md)

This list will expand in the future.
