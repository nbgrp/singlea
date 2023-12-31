<?php
// SPDX-License-Identifier: BSD-3-Clause

declare(strict_types=1);

namespace SingleA\Bundles\Singlea\Tests\EventListener\Security;

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use SingleA\Bundles\Singlea\EventListener\Security\LogoutListener;
use SingleA\Bundles\Singlea\Service\Authentication\AuthenticationServiceInterface;
use SingleA\Bundles\Singlea\Service\Realm\RealmResolverInterface;
use SingleA\Bundles\Singlea\Service\UserAttributes\UserAttributesManagerInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\NullToken;
use Symfony\Component\Security\Core\User\InMemoryUser;
use Symfony\Component\Security\Http\Authenticator\Token\PostAuthenticationToken;
use Symfony\Component\Security\Http\Event\LogoutEvent;

/**
 * @covers \SingleA\Bundles\Singlea\EventListener\Security\LogoutListener
 *
 * @internal
 */
final class LogoutListenerTest extends TestCase
{
    public function testNoToken(): void
    {
        $event = new LogoutEvent(Request::create(''), null);

        $authenticationService = $this->createMock(AuthenticationServiceInterface::class);
        $authenticationService
            ->expects(self::once())
            ->method('makeRedirect')
            ->willReturn(new RedirectResponse('/some/url'))
        ;

        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects(self::never())
            ->method('debug')
        ;

        $listener = new LogoutListener(
            'tkt',
            'example.test',
            Cookie::SAMESITE_STRICT,
            $authenticationService,
            $this->createStub(RealmResolverInterface::class),
            $this->createStub(UserAttributesManagerInterface::class),
            $logger,
        );

        $listener->onLogout($event);
    }

    public function testTokenWithoutTicket(): void
    {
        $request = Request::create('');
        $token = new NullToken();
        $event = new LogoutEvent($request, $token);
        $response = new RedirectResponse('/redirect_uri');

        $authenticationService = $this->createMock(AuthenticationServiceInterface::class);
        $authenticationService
            ->expects(self::once())
            ->method('makeRedirect')
            ->with($request)
            ->willReturn($response)
        ;

        $realmResolver = $this->createMock(RealmResolverInterface::class);
        $realmResolver
            ->expects(self::never())
            ->method('resolve')
        ;

        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects(self::once())
            ->method('debug')
        ;

        $listener = new LogoutListener(
            'tkt',
            'example.test',
            Cookie::SAMESITE_LAX,
            $authenticationService,
            $realmResolver,
            $this->createStub(UserAttributesManagerInterface::class),
            $logger,
        );

        $listener->onLogout($event);

        self::assertSame($response, $event->getResponse());
    }

    public function testCompleteOnLogout(): void
    {
        $request = Request::create('');
        $token = new PostAuthenticationToken(new InMemoryUser('tester', null), 'test', []);
        $token->setAttribute('ticket', 'ticket-value');
        $event = new LogoutEvent($request, $token);
        $response = new RedirectResponse('/redirect_uri');

        $authenticationService = $this->createMock(AuthenticationServiceInterface::class);
        $authenticationService
            ->expects(self::once())
            ->method('makeRedirect')
            ->with($request)
            ->willReturn($response)
        ;

        $realmResolver = $this->createMock(RealmResolverInterface::class);
        $realmResolver
            ->expects(self::once())
            ->method('resolve')
            ->with($request)
        ;

        $userAttributesManager = $this->createMock(UserAttributesManagerInterface::class);
        $userAttributesManager
            ->expects(self::once())
            ->method('remove')
            ->with(
                self::anything(),
                'ticket-value',
            )
        ;

        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects(self::once())
            ->method('debug')
        ;

        $listener = new LogoutListener(
            'tkt',
            'example.test',
            Cookie::SAMESITE_NONE,
            $authenticationService,
            $realmResolver,
            $userAttributesManager,
            $logger,
        );

        $listener->onLogout($event);

        self::assertSame($response, $event->getResponse());

        $ticketCookie = array_filter($response->headers->getCookies(), static fn (Cookie $cookie): bool => $cookie->getName() === 'tkt');
        self::assertArrayHasKey(0, $ticketCookie);

        $ticketCookie = $ticketCookie[0];
        self::assertSame(1, $ticketCookie->getExpiresTime());
        self::assertSame(Cookie::SAMESITE_NONE, $ticketCookie->getSameSite());
        self::assertTrue($ticketCookie->isSecure());
    }
}
