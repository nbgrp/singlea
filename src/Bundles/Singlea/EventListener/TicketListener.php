<?php declare(strict_types=1);
// SPDX-License-Identifier: BSD-3-Clause

namespace SingleA\Bundles\Singlea\EventListener;

use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

final class TicketListener
{
    public const TICKET_ATTRIBUTE = '__ticket';

    public function __construct(
        private string $ticketHeader,
        private ?LoggerInterface $logger = null,
    ) {}

    #[AsEventListener(KernelEvents::REQUEST, priority: 30)]
    public function decodeTicket(RequestEvent $event): void
    {
        $request = $event->getRequest();

        $encodedTicket = trim((string) $request->headers->get($this->ticketHeader));
        if (!$encodedTicket) {
            return;
        }

        try {
            $request->attributes->set(
                self::TICKET_ATTRIBUTE,
                sodium_base642bin($encodedTicket, \SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING),
            );
        } catch (\SodiumException $exception) {
            $this->logger?->notice('Cannot decode invalid ticket: '.$exception->getMessage(), [$encodedTicket]);

            throw new UnauthorizedHttpException('Ticket', 'Invalid ticket.', $exception);
        }
    }
}
