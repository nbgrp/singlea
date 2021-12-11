<?php declare(strict_types=1);
// SPDX-License-Identifier: BSD-3-Clause

namespace SingleA\Bundles\Singlea\Tests\DependencyInjection\Compiler;

use PHPUnit\Framework\TestCase;
use SingleA\Bundles\Singlea\DependencyInjection\Compiler\CachePoolPass;
use SingleA\Bundles\Singlea\Service\UserAttributes\UserAttributesManager;
use SingleA\Bundles\Singlea\Service\UserAttributes\UserAttributesManagerInterface;
use Symfony\Component\DependencyInjection\Argument\AbstractArgument;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;

/**
 * @covers \SingleA\Bundles\Singlea\DependencyInjection\Compiler\CachePoolPass
 *
 * @internal
 */
final class CachePoolPassTest extends TestCase
{
    public function testSuccessfulProcess(): void
    {
        $container = new ContainerBuilder();
        $firewalls = ['main', 'second', 'named'];
        $container->setParameter('security.firewalls', $firewalls);

        $userAttributesManagerDefinition = new Definition(UserAttributesManager::class, [new AbstractArgument('cache pools')]);
        $container->setDefinition(UserAttributesManagerInterface::class, $userAttributesManagerDefinition);

        $baseCachePoolDefinition = new Definition();
        $container->setDefinition('singlea.cache', $baseCachePoolDefinition);

        $secondFirewallCachePoolDefinition = new Definition();
        $secondFirewallCachePoolDefinition->addTag('cache.pool');
        $container->setDefinition('singlea.cache.second', $secondFirewallCachePoolDefinition);

        $namedFirewallCachePoolDefinition = new Definition();
        $namedFirewallCachePoolDefinition->addTag('cache.pool', ['name' => 'singlea.cache.named']);
        $container->setDefinition('singlea.cache_pool_with_tagged_name', $namedFirewallCachePoolDefinition);

        $sideCachePoolDefinition = new Definition();
        $sideCachePoolDefinition->addTag('cache.pool');
        $container->setDefinition('some.cache.pool', $sideCachePoolDefinition);

        $namedSideCachePoolDefinition = new Definition();
        $namedSideCachePoolDefinition->addTag('cache.pool', ['name' => 'other.cache.named']);
        $container->setDefinition('singlea.cache_side_pool_with_tagged_name', $namedSideCachePoolDefinition);

        (new CachePoolPass())->process($container);

        self::assertTrue($container->hasDefinition('singlea.cache.main'));

        $mainCachePoolDefinition = $container->getDefinition('singlea.cache.main');
        self::assertInstanceOf(ChildDefinition::class, $mainCachePoolDefinition);
        self::assertSame('singlea.cache', $mainCachePoolDefinition->getParent());

        $pools = $userAttributesManagerDefinition->getArgument(0);
        self::assertCount(3, $pools);
        foreach ($firewalls as $firewall) {
            self::assertArrayHasKey($firewall, $pools);
        }
    }

    public function testProcessWithoutFirewalls(): void
    {
        $container = $this->createMock(ContainerBuilder::class);
        $container
            ->expects(self::once())
            ->method('hasParameter')
            ->with('security.firewalls')
            ->willReturn(false)
        ;
        $container
            ->expects(self::never())
            ->method('hasDefinition')
        ;

        (new CachePoolPass())->process($container);
    }

    public function testFailedProcessWithoutBasePool(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('security.firewalls', ['main']);

        $pass = new CachePoolPass();

        $this->expectException(ServiceNotFoundException::class);
        $this->expectExceptionMessage('Cache pool "singlea.cache" must be defined in cache settings. It used as a parent for realm-specific (firewall-specific) user attributes cache pools.');

        $pass->process($container);
    }

    public function testFailedProcessWithoutUserAttributesManager(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('security.firewalls', ['main']);

        $baseCachePoolDefinition = new Definition();
        $container->setDefinition('singlea.cache', $baseCachePoolDefinition);

        $pass = new CachePoolPass();

        $this->expectException(ServiceNotFoundException::class);
        $this->expectExceptionMessage('You have requested a non-existent service "SingleA\Bundles\Singlea\Service\UserAttributes\UserAttributesManagerInterface".');

        $pass->process($container);
    }
}
