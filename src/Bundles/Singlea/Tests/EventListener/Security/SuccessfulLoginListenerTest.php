<?php
// SPDX-License-Identifier: BSD-3-Clause

declare(strict_types=1);

namespace SingleA\Bundles\Singlea\Tests\EventListener\Security;

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use SingleA\Bundles\Singlea\Event\UserAttributesEvent;
use SingleA\Bundles\Singlea\EventListener\Security\SuccessfulLoginListener;
use SingleA\Bundles\Singlea\Service\Realm\RealmResolverInterface;
use SingleA\Bundles\Singlea\Service\UserAttributes\UserAttributesManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\User\InMemoryUser;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authenticator\AuthenticatorInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\Authenticator\Token\PostAuthenticationToken;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

/**
 * @covers \SingleA\Bundles\Singlea\Event\UserAttributesEvent
 * @covers \SingleA\Bundles\Singlea\EventListener\Security\SuccessfulLoginListener
 *
 * @internal
 */
final class SuccessfulLoginListenerTest extends TestCase
{
    /**
     * @dataProvider provideSuccessfulSetUserAttributesCases
     */
    public function testSuccessfulSetUserAttributes(Request $request, ?string $ticketFromCookie): void
    {
        $ticket = 'ticket-value';
        $user = new InMemoryUser('tester', null);
        $passport = new SelfValidatingPassport(new UserBadge('tester', static fn (string $identifier): UserInterface => $user));
        $token = new PostAuthenticationToken($user, 'test', []);

        $event = new LoginSuccessEvent(
            $this->createStub(AuthenticatorInterface::class),
            $passport,
            $token,
            $request,
            null,
            'test',
        );

        $realmResolver = $this->createMock(RealmResolverInterface::class);
        $realmResolver
            ->expects(self::once())
            ->method('resolve')
            ->with($request)
        ;

        $userAttributesManager = $this->createMock(UserAttributesManagerInterface::class);
        $userAttributesManager
            ->expects(self::once())
            ->method('persist')
            ->with(
                self::anything(),
                'tester',
                [
                    'attr1' => 'v1',
                    'attr2' => 'v2',
                ],
                $ticketFromCookie,
            )
            ->willReturn($ticket)
        ;

        $eventDispatcher = new EventDispatcher();
        $eventDispatcher->addListener(UserAttributesEvent::class, static function (UserAttributesEvent $event): void {
            $event->setUserAttributes([
                'attr1' => 'v1',
                'attr2' => 'v2',
            ]);
        });

        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects(self::once())
            ->method('debug')
        ;

        $listener = new SuccessfulLoginListener(
            'tkt',
            $realmResolver,
            $userAttributesManager,
            $eventDispatcher,
            $logger,
        );

        $listener->setUserAttributes($event);

        self::assertSame('ticket-value', $event->getRequest()->attributes->get('__ticket'));
        self::assertSame('ticket-value', $event->getAuthenticatedToken()->getAttribute('ticket'));
    }

    public function provideSuccessfulSetUserAttributesCases(): iterable
    {
        yield 'Ticket in cookies' => [
            'request' => Request::create('', cookies: ['tkt' => 'Y29va2llLXRpY2tldA']),
            'ticketFromCookie' => 'cookie-ticket',
        ];

        yield 'No ticket in cookies' => [
            'request' => Request::create(''),
            'ticketFromCookie' => null,
        ];
    }

    public function testInvalidCookieTicket(): void
    {
        $user = new InMemoryUser('tester', null);
        $passport = new SelfValidatingPassport(new UserBadge('tester', static fn (string $identifier): UserInterface => $user));
        $token = new PostAuthenticationToken($user, 'test', []);

        $event = new LoginSuccessEvent(
            $this->createStub(AuthenticatorInterface::class),
            $passport,
            $token,
            Request::create('', cookies: ['tkt' => 'invalid-ticket']),
            null,
            'test',
        );

        $userAttributesManager = $this->createMock(UserAttributesManagerInterface::class);
        $userAttributesManager
            ->expects(self::never())
            ->method('persist')
        ;

        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects(self::once())
            ->method('notice')
            ->with(self::anything(), ['invalid-ticket'])
        ;
        $logger
            ->expects(self::never())
            ->method('debug')
        ;

        $listener = new SuccessfulLoginListener(
            'tkt',
            $this->createStub(RealmResolverInterface::class),
            $userAttributesManager,
            new EventDispatcher(),
            $logger,
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot decode ticket from cookie.');

        $listener->setUserAttributes($event);
    }
}
