<?php
// SPDX-License-Identifier: BSD-3-Clause

declare(strict_types=1);

namespace SingleA\Bundles\Singlea\Tests\Service\Authentication;

use PHPUnit\Framework\TestCase;
use SingleA\Bundles\Singlea\Event\LoginEvent;
use SingleA\Bundles\Singlea\Service\Authentication\AuthenticationService;
use SingleA\Bundles\Singlea\Service\Realm\RealmResolverInterface;
use SingleA\Bundles\Singlea\Service\UserAttributes\UserAttributesManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authenticator\Token\PostAuthenticationToken;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @covers \SingleA\Bundles\Singlea\Event\LoginEvent
 * @covers \SingleA\Bundles\Singlea\Service\Authentication\AuthenticationService
 *
 * @internal
 */
final class AuthenticationServiceTest extends TestCase
{
    private static Request $request;

    public static function setUpBeforeClass(): void
    {
        self::$request = Request::create('', parameters: ['redirect_uri' => '/target']);
    }

    public function testNeedLogout(): void
    {
        $token = new PostAuthenticationToken($this->createStub(UserInterface::class), 'test', []);
        $token->setAttribute('ticket', 'ticket-value');

        $realmResolver = $this->createMock(RealmResolverInterface::class);
        $realmResolver
            ->expects(self::once())
            ->method('resolve')
            ->with(self::$request)
            ->willReturn('main')
        ;

        $userAttributesManager = $this->createMock(UserAttributesManagerInterface::class);
        $userAttributesManager
            ->expects(self::once())
            ->method('exists')
            ->with('main', 'ticket-value')
        ;

        $service = new AuthenticationService(
            'redirect_uri',
            $realmResolver,
            $userAttributesManager,
            $this->createStub(EventDispatcherInterface::class),
        );

        $service->needLogout($token, self::$request);
    }

    public function testHandleLogin(): void
    {
        $token = new PostAuthenticationToken($this->createStub(UserInterface::class), 'test', []);
        $token->setAttribute('ticket', 'ticket-value');

        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher
            ->expects(self::once())
            ->method('dispatch')
            ->with(self::isInstanceOf(LoginEvent::class))
            ->willReturnArgument(0)
        ;

        $service = new AuthenticationService(
            'redirect_uri',
            $this->createStub(RealmResolverInterface::class),
            $this->createStub(UserAttributesManagerInterface::class),
            $eventDispatcher,
        );

        $service->handleLogin($token, self::$request);
    }

    public function testMakeRedirect(): void
    {
        $service = new AuthenticationService(
            'redirect_uri',
            $this->createStub(RealmResolverInterface::class),
            $this->createStub(UserAttributesManagerInterface::class),
            $this->createStub(EventDispatcherInterface::class),
        );

        $response = $service->makeRedirect(self::$request);

        self::assertSame('/target', $response->getTargetUrl());
    }
}
