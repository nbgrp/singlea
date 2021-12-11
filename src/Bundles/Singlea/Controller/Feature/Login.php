<?php declare(strict_types=1);
// SPDX-License-Identifier: BSD-3-Clause

namespace SingleA\Bundles\Singlea\Controller\Feature;

use Psr\Log\LoggerInterface;
use SingleA\Bundles\Singlea\Service\Authentication\AuthenticationServiceInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Http\Event\LogoutEvent;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[AsController]
final class Login
{
    public function __invoke(
        Request $request,
        AuthenticationServiceInterface $authenticationService,
        TokenStorageInterface $tokenStorage,
        EventDispatcherInterface $eventDispatcher,
        ?LoggerInterface $logger = null,
    ): Response {
        $token = $tokenStorage->getToken();
        if (!$token) {
            throw new \RuntimeException('There is no security token.');
        }

        if ($authenticationService->needLogout($token, $request)) {
            $logger?->debug('User '.$token->getUserIdentifier().' need to be logged out.');

            $logoutEvent = $eventDispatcher->dispatch(new LogoutEvent($request, $token));

            return $logoutEvent->getResponse() ?? throw new \RuntimeException('No logout listener set the Response.');
        }

        return $authenticationService->handleLogin($token, $request);
    }
}
