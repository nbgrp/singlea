<h1 align="center"><img alt="SingleAuth" src="../assets/singleauth_outline.png"></h1>

??? quote "RFC 2119"

    The key words "MUST", "MUST NOT", "REQUIRED", "SHALL", "SHALL NOT", "SHOULD", "SHOULD NOT",
    "RECOMMENDED",  "MAY", and "OPTIONAL" in this document are to be interpreted as described in
    RFC 2119.

SingleAuth is a lightweight authentication framework which makes able to organize an interaction
between an authentication server, client application and end user in such a way that the user only
need to log in once, and then the server and client can interact on their own. And even more: any
client application using the same authentication server for access protection will be able to
interact with the server without any additional user actions: the user request to the client will be
enough.

Therefore, the SingleAuth based authentication server meets the "Single Sign-On" concept from the
user's perspective.

## Features

The SingleAuth based server must implement the following features.

* Authentication system: processing login and logout user requests and providing a special
  [_ticket_](#ticket) cookie.
* User session validation by the _ticket_.
* User token generation by the _ticket_ (if the user session is valid).

!!! info "About SingleAuth features"

    Feature is an ability of an authentication server to make done some certain bounded task. In
    addition to the mandatory features listed above, specific SingleAuth based solution can
    implement additional features. In particular, SingleA has additional
    [Request Signature](features/signature.md) and [Payload Fetcher](features/payload-fetcher.md)
    features.

The SingleAuth framework does not oblige the client to use all the features. Every client decides
what features to use, but particular SingleAuth implementation may have its own rules and demands.

### Client Registration

Every request from the client application or end user to the SingleAuth server must be made with
providing a unique client identifier and a secret, which makes it possible to distinguish requests
from different clients and process them correctly. For this reason the server must provide the
client registration method.

The basic registration method should be implemented as an endpoint for POST requests. A valid
registration request should contain JSON formatted data. A successful response must contain the
client id and secret, and may contain additional data. All this data should be JSON formatted.

A particular SingleAuth implementation may restrict client registration according to its business
logic.

### Ticket

Ticket is a core of the SingleAuth framework. It is a unique user identifier which allows the
SingleAuth client to interact with an authentication server on behalf of the user. The ticket must
be provided to the user in a corresponding cookie, which `Domain` attribute must be matchable with
all the client applications using the same SingleAuth server. For example, if you have 2
applications working on app1.domain.org and app2.domain.org, and the SingleAuth instance on
sso.domain.org, the ticket cookie must be provided with the `Domain=domain.org` attribute. This will
allow to pass the given ticket in every user request to client applications.

## SingleAuth Flow

First, the client application must be registered on the SingleAuth server side in any available way.
Afterwards, the client id and secret must be included in every request between the SingleAuth server
and the client application or end user.

When the user doing the very first request to any client application protected by the SingleAuth
server, authentication has not yet been performed and the user session does not exist. So the ticket
cookie does not exist too and the user must be logged in.

!!! warning ""

    If the processing request is an XHR request, it's processing must be immediately finished with
    an HTTP error **Unauthorized 401**.

After successful authorization the user should be redirected to the initial request URI with
preservation of GET parameters. The redirect response must contain the ticket cookie, which will be
used further to interact between the SingleAuth client and server without user participation.

If the request being processed contains the ticket cookie, the SingleA client validates the user
session using an internal request to the SingleA server that includes the ticket value. If the
validation was failed, the behavior is the same as if the ticket did not exist.

When the validity of the user session is confirmed, the SingleA client can fetch the user token
using an internal request to the server (included the ticket value). If the token was successfully
received, it is appended to the initial request into the `Authorization` header, and the request is
passed to the client application for further processing. Otherwise, an HTTP error **Unauthorized
401** must be returned.

!!! info

    As noted above, it is not necessary to use all the SingleA server features (the user
    authentication, user session validation and user token generation). The client application may
    need only certain features, e.g. the user session validation without fetching the user token,
    or fetching of the user token without redirecting the user to the login process.

### Flowchart

``` mermaid
%%{init: {
    "flowchart": { "useMaxWidth": false }
}}%%
graph TB
    U([User])
    R[/Request/]
    401([Unauthorized 401])
    App([Client App])

    subgraph client [SingleAuth Client]
        HT{Does the request<br>have a Ticket?}
        XHR{XHR?}
        V[[Validate the user session]]
        VQ{Is the user session valid?}
        T[[Fetch the user token]]
        TR{The token received?}
        AR[[Add the user token to the request]]
    end

    subgraph server [SingleAuth Server]
        L[\Login/]
        A{{Authorized}}
        VS[Validator]
        TS[Tokenizer]
    end

    U -->|Makes a request to the Client Application| R
    R --> HT
    HT -->|Yes| V
    HT -->|No| XHR
    XHR -->|Yes| 401
    V -.->|Provide the client id, the secret<br>and the user ticket| VS
    VS -.-> VQ
    VQ -->|Yes| T
    VQ -->|No| XHR
    T -.->|Provide the client id, the secret<br>and the user ticket| TS
    TS -.-> TR
    TR -->|Yes| AR
    TR -->|No| 401
    AR -->|Pass the request to the Client Application| App

    XHR -->|"No, redirect user to login<br>providing client id, secret<br>and initial request URI<br>as a return URL"| L
    L -->|Set ticket cookie| A
    A --> R

    style client fill:none,stroke:#006cb4,stroke-width:4px,stroke-dasharray: 5 5
    style server fill:none,stroke:#006cb4,stroke-width:4px

    style U fill:#3ef1e530,stroke:#3ef1e5,stroke-width:3px
    style 401 fill:#ff888830,stroke:#ff8888,stroke-width:3px
    style App fill:#55d35a30,stroke:#55d35a,stroke-width:3px
```

## Inspired by

The creation of the SingleAuth framework was inspired by such well-known and reliable protocols as:

* [OAuth 2.0](https://oauth.net/2/) and [OpenID Connect](https://openid.net/connect/)
* [CAS](https://apereo.github.io/cas/6.5.x/protocol/CAS-Protocol-Specification.html)
* [Kerberos](http://web.mit.edu/kerberos/krb5-current/doc/)

