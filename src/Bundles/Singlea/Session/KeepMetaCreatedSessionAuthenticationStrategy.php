<?php declare(strict_types=1);
// SPDX-License-Identifier: BSD-3-Clause

namespace SingleA\Bundles\Singlea\Session;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Session\SessionAuthenticationStrategyInterface;

final class KeepMetaCreatedSessionAuthenticationStrategy implements SessionAuthenticationStrategyInterface
{
    public const INITIAL_META_CREATED = '_imc';

    public function __construct(
        private readonly SessionAuthenticationStrategyInterface $sessionAuthenticationStrategy,
    ) {}

    public function onAuthentication(Request $request, TokenInterface $token): void
    {
        if ($request->hasSession(true)) {
            $session = $request->getSession();
            $session->set(self::INITIAL_META_CREATED, $session->getMetadataBag()->getCreated());
        }

        $this->sessionAuthenticationStrategy->onAuthentication($request, $token);
    }
}
