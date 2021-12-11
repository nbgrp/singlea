<?php declare(strict_types=1);
// SPDX-License-Identifier: BSD-3-Clause

namespace SingleA\Bundles\Singlea\Tests\Controller\Feature;

use PHPUnit\Framework\TestCase;
use SingleA\Bundles\Singlea\Controller\Feature\Logout;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Http\Event\LogoutEvent;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @covers \SingleA\Bundles\Singlea\Controller\Feature\Logout
 *
 * @internal
 */
final class LogoutTest extends TestCase
{
    public function testLogout(): void
    {
        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage
            ->expects(self::once())
            ->method('getToken')
            ->willReturn(null)
        ;

        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher
            ->expects(self::once())
            ->method('dispatch')
            ->with(self::isInstanceOf(LogoutEvent::class))
            ->willReturn((static function (LogoutEvent $event): LogoutEvent {
                $event->setResponse(new RedirectResponse('/url'));

                return $event;
            })(new LogoutEvent(Request::create(''), null)))
        ;

        (new Logout())(
            Request::create(''),
            $tokenStorage,
            $eventDispatcher,
        );
    }
}
