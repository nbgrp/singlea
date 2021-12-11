<?php declare(strict_types=1);
// SPDX-License-Identifier: BSD-3-Clause

namespace SingleA\Bundles\Singlea\Tests\Controller\Feature;

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use SingleA\Bundles\Singlea\Controller\Feature\Login;
use SingleA\Bundles\Singlea\Service\Authentication\AuthenticationServiceInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\InMemoryUser;
use Symfony\Component\Security\Http\Authenticator\Token\PostAuthenticationToken;
use Symfony\Component\Security\Http\Event\LogoutEvent;

/**
 * @covers \SingleA\Bundles\Singlea\Controller\Feature\Login
 *
 * @internal
 */
final class LoginTest extends TestCase
{
    public function testSuccessfulLogin(): void
    {
        $token = new PostAuthenticationToken(new InMemoryUser('tester', null), 'test', []);
        $request = Request::create('');

        $tokenStorage = new TokenStorage();
        $tokenStorage->setToken($token);

        $authenticationService = $this->createMock(AuthenticationServiceInterface::class);
        $authenticationService
            ->expects(self::once())
            ->method('needLogout')
            ->with($token, $request)
            ->willReturn(false)
        ;
        $authenticationService
            ->expects(self::once())
            ->method('handleLogin')
            ->with($token, $request)
            ->willReturn(new Response())
        ;

        (new Login())(
            $request,
            $authenticationService,
            $tokenStorage,
            new EventDispatcher(),
        );
    }

    public function testLogout(): void
    {
        $token = new PostAuthenticationToken(new InMemoryUser('tester', null), 'test', []);

        $request = Request::create('');

        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage
            ->expects(self::once())
            ->method('getToken')
            ->willReturn($token)
        ;

        $authenticationService = $this->createMock(AuthenticationServiceInterface::class);
        $authenticationService
            ->expects(self::once())
            ->method('needLogout')
            ->with($token, $request)
            ->willReturn(true)
        ;
        $authenticationService
            ->expects(self::never())
            ->method('handleLogin')
        ;

        $eventDispatcher = new EventDispatcher();
        $eventDispatcher->addListener(LogoutEvent::class, static function (LogoutEvent $event): LogoutEvent {
            $event->setResponse(new RedirectResponse('/some/path'));

            return $event;
        });

        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects(self::once())
            ->method('debug')
        ;

        $response = (new Login())(
            $request,
            $authenticationService,
            $tokenStorage,
            $eventDispatcher,
            $logger,
        );

        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertSame('/some/path', $response->getTargetUrl());
    }

    public function testNoToken(): void
    {
        $controller = new Login();
        $tokenStorage = new TokenStorage();

        $request = Request::create('');
        $authenticationService = $this->createStub(AuthenticationServiceInterface::class);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('There is no security token.');

        $controller(
            $request,
            $authenticationService,
            $tokenStorage,
            new EventDispatcher(),
        );
    }
}
