<?php declare(strict_types=1);
// SPDX-License-Identifier: BSD-3-Clause

namespace SingleA\Bundles\Singlea\Tests\Service\Realm;

use PHPUnit\Framework\TestCase;
use SingleA\Bundles\Singlea\Service\Realm\RealmResolver;
use Symfony\Bundle\SecurityBundle\Security\FirewallConfig;
use Symfony\Bundle\SecurityBundle\Security\FirewallContext;
use Symfony\Bundle\SecurityBundle\Security\FirewallMap;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestMatcher;

/**
 * @covers \SingleA\Bundles\Singlea\Service\Realm\RealmResolver
 *
 * @internal
 */
final class RealmResolverTest extends TestCase
{
    private static ContainerBuilder $container;

    public static function setUpBeforeClass(): void
    {
        self::$container = new ContainerBuilder();
        self::$container->set('main', new FirewallContext(
            [],
            config: new FirewallConfig('main', 'user-checker'),
        ));
    }

    public function testSuccessfulResolve(): void
    {
        $resolver = new RealmResolver(new FirewallMap(self::$container, [
            'main' => null,
        ]));

        $request = Request::create('');
        $request->attributes->set('_firewall_context', 'main');

        self::assertSame('main', $resolver->resolve($request));
    }

    public function testFailedResolve(): void
    {
        $resolver = new RealmResolver(new FirewallMap(self::$container, [
            'main' => new RequestMatcher('/unreachable'),
        ]));

        $request = Request::create('');
        $request->attributes->set('_firewall_context', 'test');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Firewall config unreachable.');

        $resolver->resolve($request);
    }
}
