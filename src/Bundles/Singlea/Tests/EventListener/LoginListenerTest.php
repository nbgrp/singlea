<?php declare(strict_types=1);
// SPDX-License-Identifier: BSD-3-Clause

namespace SingleA\Bundles\Singlea\Tests\EventListener;

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use SingleA\Bundles\Singlea\Event\LoginEvent;
use SingleA\Bundles\Singlea\EventListener\LoginListener;
use SingleA\Bundles\Singlea\Service\Realm\RealmResolverInterface;
use SingleA\Bundles\Singlea\Service\UserAttributes\UserAttributesManagerInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @covers \SingleA\Bundles\Singlea\Event\LoginEvent
 * @covers \SingleA\Bundles\Singlea\EventListener\LoginListener
 *
 * @internal
 */
final class LoginListenerTest extends TestCase
{
    /**
     * @testWith [600, "lax", false]
     *           [900, "none", true]
     */
    public function testSetTicketCookie(int $ttl, string $sameSite, bool $expectedSecure): void
    {
        $request = Request::create('');
        $response = new Response();

        $listener = new LoginListener(
            'tkt',
            $ttl,
            'example.test',
            $sameSite,
            false,
            $this->createStub(RealmResolverInterface::class),
            $this->createStub(UserAttributesManagerInterface::class),
        );

        $listener->setTicketCookie(new LoginEvent($request, $response, 'ticket-value'));

        $ticketCookies = array_filter($response->headers->getCookies(), static fn (Cookie $cookie): bool => $cookie->getName() === 'tkt');
        self::assertCount(1, $ticketCookies);

        $ticketCookie = reset($ticketCookies);
        self::assertSame('dGlja2V0LXZhbHVl', $ticketCookie->getValue());
        self::assertSame($ttl, $ticketCookie->getMaxAge());
        self::assertSame('example.test', $ticketCookie->getDomain());
        self::assertSame($sameSite, $ticketCookie->getSameSite());
        self::assertSame($expectedSecure, $ticketCookie->isSecure());
    }

    public function testNoStickSession(): void
    {
        $realmResolver = $this->createMock(RealmResolverInterface::class);
        $realmResolver
            ->expects(self::never())
            ->method('resolve')
        ;

        $listener = new LoginListener(
            'tkt',
            600,
            'example.test',
            Cookie::SAMESITE_NONE,
            false,
            $realmResolver,
            $this->createStub(UserAttributesManagerInterface::class),
        );

        $listener->stickSession(new LoginEvent(Request::create(''), new Response(), 'ticket-value'));
    }

    public function testStickSession(): void
    {
        $request = Request::create('');
        $response = new Response();

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
            ->method('prolong')
            ->with('main', 'ticket-value')
        ;

        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects(self::once())
            ->method('debug')
        ;

        $listener = new LoginListener(
            'tkt',
            600,
            'example.test',
            Cookie::SAMESITE_NONE,
            true,
            $realmResolver,
            $userAttributesManager,
            $logger,
        );

        $listener->stickSession(new LoginEvent($request, $response, 'ticket-value'));
    }
}
