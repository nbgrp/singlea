<?php declare(strict_types=1);
// SPDX-License-Identifier: BSD-3-Clause

namespace SingleA\Bundles\Singlea\Controller\Feature;

use Psr\Log\LoggerInterface;
use SingleA\Bundles\Singlea\Service\Authentication\AuthenticationServiceInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

#[AsController]
final class Login
{
    public function __invoke(
        Request $request,
        AuthenticationServiceInterface $authenticationService,
        TokenStorageInterface $tokenStorage,
        ?LoggerInterface $logger = null,
    ): Response {
        $token = $tokenStorage->getToken();
        if (!$token) {
            throw new \RuntimeException('There is no security token.');
        }

        if ($authenticationService->needLogout($token, $request)) {
            $tokenStorage->setToken();
            $request->getSession()->invalidate();

            $logger?->debug('User '.$token->getUserIdentifier().' logged out during login.');

            return new RedirectResponse($request->getUri());
        }

        return $authenticationService->handleLogin($token, $request);
    }
}
