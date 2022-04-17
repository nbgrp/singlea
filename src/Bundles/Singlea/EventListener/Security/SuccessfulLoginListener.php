<?php declare(strict_types=1);
// SPDX-License-Identifier: BSD-3-Clause

namespace SingleA\Bundles\Singlea\EventListener\Security;

use Psr\Log\LoggerInterface;
use SingleA\Bundles\Singlea\Event\UserAttributesEvent;
use SingleA\Bundles\Singlea\EventListener\TicketListener;
use SingleA\Bundles\Singlea\Service\Realm\RealmResolverInterface;
use SingleA\Bundles\Singlea\Service\UserAttributes\UserAttributesManagerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class SuccessfulLoginListener
{
    public function __construct(
        private readonly string $ticketCookieName,
        private readonly RealmResolverInterface $realmResolver,
        private readonly UserAttributesManagerInterface $userAttributesManager,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly ?LoggerInterface $logger = null,
    ) {}

    #[AsEventListener(LoginSuccessEvent::class)]
    public function setUserAttributes(LoginSuccessEvent $event): void
    {
        $request = $event->getRequest();

        $userAttributesEvent = $this->eventDispatcher->dispatch(new UserAttributesEvent(
            $event->getPassport(),
            $event->getAuthenticatedToken(),
            $request,
        ));

        $ticket = $this->userAttributesManager->persist(
            $this->realmResolver->resolve($request),
            $event->getUser()->getUserIdentifier(),
            $userAttributesEvent->getUserAttributes(),
            $this->getTicketFromCookie($request),
        );
        $this->logger?->debug('User attributes for '.$event->getUser()->getUserIdentifier().' initialized.');

        $event->getAuthenticatedToken()->setAttribute('ticket', $ticket);
        $request->attributes->set(TicketListener::TICKET_ATTRIBUTE, $ticket);
    }

    private function getTicketFromCookie(Request $request): ?string
    {
        $ticket = $request->cookies->get($this->ticketCookieName);
        if (!\is_string($ticket)) {
            return null;
        }

        try {
            return sodium_base642bin($ticket, \SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING);
        } catch (\SodiumException $exception) {
            $this->logger?->notice('Cannot decode ticket from cookie: '.$exception->getMessage(), [$ticket]);

            throw new \InvalidArgumentException('Cannot decode ticket from cookie.', previous: $exception);
        }
    }
}
