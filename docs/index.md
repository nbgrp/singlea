<h1 class="s-header">
  <img alt="SingleA" src="assets/singlea.png">
</h1>

Have you ever noticed that existing Single Sign-On solutions do not provide truly ***single***
sign-on behavior? Even though the user goes through the full authentication cycle during the very
first accessing of protected application, the following very first accessing of another applications
which protected by the same SSO lead to recurrence login attempt. Fortunately, it does not need to
re-enter the login and password, since the user is already authenticated. But how does it work? And
what limitations will you face if your very first request to the application is XHR?

Or maybe you have ever had to migrate a monolithic system to multiple microservices? In that case
you may know how complex and cumbersome the user data migration task can be. And how would you feel
about the following idea: you can do not migrate the user data at all and get it from the request,
without overhead and security risks?

## What is SingleA

SingleA is a Symfony bundles set which allows rapid creating of ***single*** sign-on service. You
would be able to interact with any resources protected by SingleA instance after a once user login.
Re-login will be required only after the user logout or a session expiration.

SingleA makes able to append the initial request
with [authorization token](features/tokenization.md) that can contain user data. Basically,
this data is composed of the user attributes,
but [Payload Fetcher](features/payload-fetcher.md) feature allows receiving additional data to
complete the token payload. Depending on the chosen
[features](features/about.md) implementation, the data of payload fetching requests and
responses, as well as the token, can be signed and encrypted. The token has a lifetime that allows
to cache it and prevent sending redundant requests.

SingleA ensures the security of not only transmitted data, but also clients configurations and user
data stored on the service side. To do this, all data stored encrypted. Encryption is performed
using pairs of rotatable keys and secrets (known only to the client application or end user).

## Further documentation

* [How It Works](how-it-works.md) is the best way to understand the whole requests cycle
  between the client application, end user and SingleA instance.
* [Features](features/about.md) section describes the key design principle which makes SingleA
  really flexible and able to solve the most complicated issues.
* Bundles section contains documentation for each official bundle (included in this project
  structure). And the first of them is the main [SingleA Bundle](bundles/singlea.md).
* [SingleAuth](singleauth.md) is an authentication framework (not quite a protocol) on which
  SingleA based (and which gave the name of the project). Read about it to get fully understand
  abilities of SingleA.

## Created by

The SingleA project was developed by Alexander Menshchikov with gratitude to numerous friends and
colleagues who inspired him to this project development.

SingleA is among the FOSS projects of [nb:group](https://nbgrp.org) responsible for its further
development and support.
