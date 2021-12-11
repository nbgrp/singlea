<?php declare(strict_types=1);
// SPDX-License-Identifier: BSD-3-Clause

namespace SingleA\Bundles\Singlea\Controller\Feature;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Http\Event\LogoutEvent;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[AsController]
final class Logout
{
    public function __invoke(
        Request $request,
        TokenStorageInterface $tokenStorage,
        EventDispatcherInterface $eventDispatcher,
    ): Response {
        /** @var LogoutEvent $logoutEvent */
        $logoutEvent = $eventDispatcher->dispatch(new LogoutEvent($request, $tokenStorage->getToken()));

        return $logoutEvent->getResponse() ?? throw new \RuntimeException('No logout listener set the Response.');
    }
}
