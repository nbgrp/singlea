<?php declare(strict_types=1);
// SPDX-License-Identifier: BSD-3-Clause

namespace SingleA\Bundles\Singlea\Tests\Controller\Feature;

use PHPUnit\Framework\TestCase;
use SingleA\Bundles\Singlea\Controller\Feature\Verify;
use SingleA\Bundles\Singlea\Service\Realm\RealmResolverInterface;
use SingleA\Bundles\Singlea\Service\UserAttributes\UserAttributesManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * @covers \SingleA\Bundles\Singlea\Controller\Feature\Verify
 *
 * @internal
 */
final class VerifyTest extends TestCase
{
    public function testSuccessfulVerify(): void
    {
        $request = Request::create('');
        $request->attributes->set('__ticket', 'ticket-value');

        $realmResolver = $this->createMock(RealmResolverInterface::class);
        $realmResolver
            ->expects(self::once())
            ->method('resolve')
            ->with($request)
            ->willReturn('main')
        ;

        $userAttributesManager = $this->createMock(UserAttributesManagerInterface::class);
        $userAttributesManager
            ->expects(self::once())
            ->method('exists')
            ->with('main', 'ticket-value')
            ->willReturn(true)
        ;

        $response = (new Verify())($request, $realmResolver, $userAttributesManager);

        self::assertSame(200, $response->getStatusCode());
    }

    public function testFailedVerify(): void
    {
        $request = Request::create('');
        $request->attributes->set('__ticket', 'ticket-value');

        $realmResolver = $this->createMock(RealmResolverInterface::class);
        $realmResolver
            ->expects(self::once())
            ->method('resolve')
            ->with($request)
            ->willReturn('main')
        ;

        $userAttributesManager = $this->createMock(UserAttributesManagerInterface::class);
        $userAttributesManager
            ->expects(self::once())
            ->method('exists')
            ->with('main', 'ticket-value')
            ->willReturn(false)
        ;

        $controller = new Verify();

        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('There is no user cache.');

        $controller($request, $realmResolver, $userAttributesManager);
    }
}
