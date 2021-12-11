<?php declare(strict_types=1);
// SPDX-License-Identifier: BSD-3-Clause

namespace SingleA\Bundles\Singlea\Tests\DependencyInjection\Compiler;

use PHPUnit\Framework\TestCase;
use SingleA\Bundles\Singlea\DependencyInjection\Compiler\RealmRequestMatcherPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @covers \SingleA\Bundles\Singlea\DependencyInjection\Compiler\RealmRequestMatcherPass
 *
 * @internal
 */
final class RealmRequestMatcherPassTest extends TestCase
{
    public function testProcess(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('security.firewalls', ['main', 'second']);

        (new RealmRequestMatcherPass())->process($container);

        self::assertTrue($container->hasDefinition('SingleA\Bundles\Singlea\Request\RealmRequestMatcher.main'));
        self::assertSame('main', $container->getDefinition('SingleA\Bundles\Singlea\Request\RealmRequestMatcher.main')->getArgument(0));

        self::assertTrue($container->hasDefinition('SingleA\Bundles\Singlea\Request\RealmRequestMatcher.second'));
        self::assertSame('second', $container->getDefinition('SingleA\Bundles\Singlea\Request\RealmRequestMatcher.second')->getArgument(0));
    }
}
