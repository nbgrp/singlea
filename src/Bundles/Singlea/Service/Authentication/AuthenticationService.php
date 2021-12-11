<?php declare(strict_types=1);
// SPDX-License-Identifier: BSD-3-Clause

namespace SingleA\Bundles\Singlea\Service\Authentication;

use SingleA\Bundles\Singlea\Event\LoginEvent;
use SingleA\Bundles\Singlea\Service\Realm\RealmResolverInterface;
use SingleA\Bundles\Singlea\Service\UserAttributes\UserAttributesManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class AuthenticationService implements AuthenticationServiceInterface
{
    public function __construct(
        private string $redirectUriQueryParameter,
        private RealmResolverInterface $realmResolver,
        private UserAttributesManagerInterface $userAttributesManager,
        private EventDispatcherInterface $eventDispatcher,
    ) {}

    public function needLogout(TokenInterface $token, Request $request): bool
    {
        $realm = $this->realmResolver->resolve($request);
        /** @var string $ticket */
        $ticket = $token->getAttribute('ticket');

        return !$this->userAttributesManager->exists($realm, $ticket);
    }

    public function handleLogin(TokenInterface $token, Request $request): Response
    {
        /** @var string $ticket */
        $ticket = $token->getAttribute('ticket');
        if (empty($ticket)) {
            throw new BadRequestHttpException('There is no request ticket.');
        }

        /** @var LoginEvent $event */
        $event = $this->eventDispatcher->dispatch(new LoginEvent(
            $request,
            $this->makeRedirect($request),
            $ticket,
        ));

        return $event->getResponse();
    }

    public function makeRedirect(Request $request): RedirectResponse
    {
        return new RedirectResponse((string) $request->query->get($this->redirectUriQueryParameter));
    }
}
