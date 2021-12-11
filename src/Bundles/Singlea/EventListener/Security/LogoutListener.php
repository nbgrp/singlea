<?php declare(strict_types=1);
// SPDX-License-Identifier: BSD-3-Clause

namespace SingleA\Bundles\Singlea\EventListener\Security;

use Psr\Log\LoggerInterface;
use SingleA\Bundles\Singlea\Service\Authentication\AuthenticationServiceInterface;
use SingleA\Bundles\Singlea\Service\Realm\RealmResolverInterface;
use SingleA\Bundles\Singlea\Service\UserAttributes\UserAttributesManagerInterface;
use SingleA\Bundles\Singlea\Utility\StringUtility;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\Security\Http\Event\LogoutEvent;

final class LogoutListener
{
    public function __construct(
        private string $ticketCookieName,
        private string $ticketDomain,
        private AuthenticationServiceInterface $authenticationService,
        private RealmResolverInterface $realmResolver,
        private UserAttributesManagerInterface $userAttributesManager,
        private ?LoggerInterface $logger = null,
    ) {
        $this->ticketDomain = StringUtility::prefix($this->ticketDomain, '.');
    }

    #[AsEventListener(LogoutEvent::class, priority: 65)]
    public function onLogout(LogoutEvent $event): void
    {
        $token = $event->getToken();
        if (!$token) {
            return;
        }

        $request = $event->getRequest();

        $response = $this->authenticationService->makeRedirect($request);
        $event->setResponse($response);

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
            secure: true,
            sameSite: Cookie::SAMESITE_NONE,
        );

        $this->logger?->debug('The ticket cookie was cleared.');
    }
}
