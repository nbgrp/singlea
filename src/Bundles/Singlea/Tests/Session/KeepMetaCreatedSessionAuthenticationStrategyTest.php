<?php declare(strict_types=1);
// SPDX-License-Identifier: BSD-3-Clause

namespace Session;

use PHPUnit\Framework\TestCase;
use SingleA\Bundles\Singlea\Session\KeepMetaCreatedSessionAuthenticationStrategy;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Security\Core\Authentication\Token\NullToken;
use Symfony\Component\Security\Http\Session\SessionAuthenticationStrategyInterface;

/**
 * @covers \SingleA\Bundles\Singlea\Session\KeepMetaCreatedSessionAuthenticationStrategy
 *
 * @internal
 */
final class KeepMetaCreatedSessionAuthenticationStrategyTest extends TestCase
{
    public function testOnAuthentication(): void
    {
        $sessionAuthenticationStrategy = $this->createMock(SessionAuthenticationStrategyInterface::class);

        $request = Request::create('');
        $request->setSession(new Session(new MockArraySessionStorage()));
        $request->getSession()->start();

        $strategy = new KeepMetaCreatedSessionAuthenticationStrategy($sessionAuthenticationStrategy);
        $strategy->onAuthentication($request, new NullToken());

        self::assertTrue($request->getSession()->has('_imc'));
    }
}
