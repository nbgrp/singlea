<?php
// SPDX-License-Identifier: BSD-3-Clause

declare(strict_types=1);

namespace SingleA\Bundles\Redis\Tests\DependencyInjection;

use PHPUnit\Framework\TestCase;
use SingleA\Bundles\Redis\DependencyInjection\SingleaRedisExtension;
use SingleA\Bundles\Redis\FeatureConfigManagerFactory;
use SingleA\Contracts\Persistence\ClientManagerInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @covers \SingleA\Bundles\Redis\DependencyInjection\SingleaRedisExtension
 *
 * @internal
 */
final class SingleaRedisExtensionTest extends TestCase
{
    public function testLoad(): void
    {
        $container = new ContainerBuilder();
        $extension = new SingleaRedisExtension();

        $container->registerExtension($extension);
        $extension->load([], $container);

        self::assertTrue($container->hasDefinition(FeatureConfigManagerFactory::class));
        self::assertTrue($container->hasDefinition(ClientManagerInterface::class));

        self::assertTrue($container->hasParameter('singlea_redis.client_last_access_key'));
        self::assertTrue($container->hasParameter('singlea_redis.snc_redis_client_name'));
    }
}
