<?php declare(strict_types=1);
// SPDX-License-Identifier: BSD-3-Clause

namespace SingleA\Bundles\Singlea\EventListener;

use Psr\Log\LoggerInterface;
use SingleA\Bundles\Singlea\Event\LoginEvent;
use SingleA\Bundles\Singlea\Service\Realm\RealmResolverInterface;
use SingleA\Bundles\Singlea\Service\UserAttributes\UserAttributesManagerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\Cookie;

final class LoginListener
{
    public function __construct(
        private string $ticketCookieName,
        private int $ticketTtl,
        private string $ticketDomain,
        private string $ticketSameSite,
        private bool $stickySession,
        private RealmResolverInterface $realmResolver,
        private UserAttributesManagerInterface $userAttributesManager,
        private ?LoggerInterface $logger = null,
    ) {}

    #[AsEventListener(LoginEvent::class)]
    public function setTicketCookie(LoginEvent $event): void
    {
        $event->getResponse()->headers->setCookie(Cookie::create(
            name: $this->ticketCookieName,
            value: sodium_bin2base64($event->getTicket(), \SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING),
            expire: time() + $this->ticketTtl,
            domain: $this->ticketDomain,
            secure: $this->ticketSameSite === Cookie::SAMESITE_NONE,
            sameSite: $this->ticketSameSite,
        ));
    }

    #[AsEventListener(LoginEvent::class)]
    public function stickSession(LoginEvent $event): void
    {
        if (!$this->stickySession) {
            return;
        }

        $realm = $this->realmResolver->resolve($event->getRequest());

        $this->userAttributesManager->prolong($realm, $event->getTicket());
        $this->logger?->debug('Session prolonged for ticket');
    }
}
