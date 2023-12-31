<?php
// SPDX-License-Identifier: BSD-3-Clause

declare(strict_types=1);

namespace SingleA\Bundles\Singlea\EventListener\Security;

use Psr\Log\LoggerInterface;
use SingleA\Bundles\Singlea\Service\Authentication\AuthenticationServiceInterface;
use SingleA\Bundles\Singlea\Service\Realm\RealmResolverInterface;
use SingleA\Bundles\Singlea\Service\UserAttributes\UserAttributesManagerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\Security\Http\Event\LogoutEvent;

final class LogoutListener
{
    public function __construct(
        private readonly string $ticketCookieName,
        private readonly string $ticketDomain,
        private readonly string $ticketSameSite,
        private readonly AuthenticationServiceInterface $authenticationService,
        private readonly RealmResolverInterface $realmResolver,
        private readonly UserAttributesManagerInterface $userAttributesManager,
        private readonly ?LoggerInterface $logger = null,
    ) {}

    #[AsEventListener(LogoutEvent::class, priority: 65)]
    public function onLogout(LogoutEvent $event): void
    {
        $request = $event->getRequest();

        $response = $this->authenticationService->makeRedirect($request);
        $event->setResponse($response);

        $token = $event->getToken();
        if (!$token) {
            return;
        }

        /** @var string $ticket */
        $ticket = $token->getAttribute('ticket');
        if (empty($ticket)) {
            $this->logger?->debug('Authentication token does not contain a ticket.');

            return;
        }

        $realm = $this->realmResolver->resolve($request);
        $this->userAttributesManager->remove($realm, $ticket);

        $response->headers->clearCookie(
            name: $this->ticketCookieName,
            domain: $this->ticketDomain,
            secure: $this->ticketSameSite === Cookie::SAMESITE_NONE,
            sameSite: $this->ticketSameSite,
        );

        $this->logger?->debug('The ticket cookie was cleared.');
    }
}
