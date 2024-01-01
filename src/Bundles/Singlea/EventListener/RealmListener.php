<?php
// SPDX-License-Identifier: BSD-3-Clause

declare(strict_types=1);

namespace SingleA\Bundles\Singlea\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final readonly class RealmListener
{
    public const REALM_ATTRIBUTE = '_singlea_realm';

    public function __construct(
        private string $realmQueryParameter,
        private string $defaultRealm,
    ) {}

    #[AsEventListener(KernelEvents::REQUEST, priority: 65)]
    public function setRequestAttribute(RequestEvent $event): void
    {
        $request = $event->getRequest();

        $realm = trim((string) $request->query->get($this->realmQueryParameter));
        if ($realm) {
            $request->attributes->set(self::REALM_ATTRIBUTE, $realm);

            return;
        }

        $request->attributes->set(self::REALM_ATTRIBUTE, $this->defaultRealm);
    }
}
