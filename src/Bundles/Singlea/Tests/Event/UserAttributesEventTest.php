<?php declare(strict_types=1);
// SPDX-License-Identifier: BSD-3-Clause

namespace SingleA\Bundles\Singlea\Tests\Event;

use PHPUnit\Framework\TestCase;
use SingleA\Bundles\Singlea\Event\UserAttributesEvent;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\User\InMemoryUser;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Token\PostAuthenticationToken;

/**
 * @covers \SingleA\Bundles\Singlea\Event\UserAttributesEvent
 *
 * @internal
 */
final class UserAttributesEventTest extends TestCase
{
    public function testEventGetters(): void
    {
        $passport = new Passport(new UserBadge('tester'), new PasswordCredentials(''));
        $token = new PostAuthenticationToken(new InMemoryUser('tester', null), 'test', []);
        $request = Request::create('');

        $event = new UserAttributesEvent(
            $passport,
            $token,
            $request,
        );

        self::assertSame($passport, $event->getPassport());
        self::assertSame($token, $event->getAuthenticatedToken());
        self::assertSame($request, $event->getRequest());
    }
}
