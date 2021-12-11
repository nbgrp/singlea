<?php declare(strict_types=1);
// SPDX-License-Identifier: BSD-3-Clause

namespace SingleA\Bundles\Singlea\Controller\Feature;

use SingleA\Bundles\Singlea\EventListener\TicketListener;
use SingleA\Bundles\Singlea\Service\Realm\RealmResolverInterface;
use SingleA\Bundles\Singlea\Service\UserAttributes\UserAttributesManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

#[AsController]
final class Verify
{
    public function __invoke(
        Request $request,
        RealmResolverInterface $realmResolver,
        UserAttributesManagerInterface $userAttributesManager,
    ): Response {
        /** @var string $ticket */
        $ticket = $request->attributes->get(TicketListener::TICKET_ATTRIBUTE);
        $realm = $realmResolver->resolve($request);

        if ($userAttributesManager->exists($realm, $ticket)) {
            return new Response();
        }

        throw new AccessDeniedHttpException('There is no user cache.');
    }
}
