<?php
// SPDX-License-Identifier: BSD-3-Clause

declare(strict_types=1);

namespace SingleA\Bundles\Singlea\EventListener;

use Psr\Log\LoggerInterface;
use SingleA\Contracts\Persistence\ClientManagerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Uid\UuidV6;

final readonly class ClientListener
{
    public const CLIENT_ID_ATTRIBUTE = '__client_id';
    public const SECRET_ATTRIBUTE = '__secret';

    public function __construct(
        private string $clientIdQueryParameterName,
        private string $secretQueryParameterName,
        private ClientManagerInterface $clientManager,
        private ?LoggerInterface $logger = null,
    ) {}

    #[AsEventListener(KernelEvents::REQUEST, priority: 31)]
    public function decodeClientId(RequestEvent $event): void
    {
        $request = $event->getRequest();

        $encodedId = $request->query->get($this->clientIdQueryParameterName);
        $encodedId = \is_string($encodedId) ? trim($encodedId) : '';
        if (!$encodedId) {
            return;
        }

        try {
            $clientId = (string) UuidV6::fromBase58($encodedId);
        } catch (\InvalidArgumentException) {
            $this->logger?->info('Invalid encoded client id: '.$encodedId);
            $clientId = '';
            // no-op
        }

        if (!$this->clientManager->exists($clientId)) {
            throw new AccessDeniedHttpException('Unknown client.');
        }

        $request->attributes->set(self::CLIENT_ID_ATTRIBUTE, $clientId);
    }

    #[AsEventListener(KernelEvents::REQUEST, priority: 30)]
    public function decodeSecret(RequestEvent $event): void
    {
        $request = $event->getRequest();

        $encodedSecret = trim((string) $request->query->get($this->secretQueryParameterName));
        if (!$encodedSecret) {
            return;
        }

        try {
            $request->attributes->set(
                self::SECRET_ATTRIBUTE,
                sodium_base642bin($encodedSecret, \SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING),
            );
        } catch (\SodiumException $exception) {
            $this->logger?->notice('Cannot decode invalid secret: '.$exception->getMessage(), [$encodedSecret]);

            throw new UnauthorizedHttpException('Secret', 'Invalid client secret.', $exception);
        }
    }
}
