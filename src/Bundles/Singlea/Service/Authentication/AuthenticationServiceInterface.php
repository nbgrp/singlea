<?php
// SPDX-License-Identifier: BSD-3-Clause

declare(strict_types=1);

namespace SingleA\Bundles\Singlea\Service\Authentication;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * The service processes all necessary work around the login/logout actions.
 */
interface AuthenticationServiceInterface
{
    public function needLogout(TokenInterface $token, Request $request): bool;

    public function handleLogin(TokenInterface $token, Request $request): Response;

    public function makeRedirect(Request $request): RedirectResponse;
}
