<?php declare(strict_types=1);
// SPDX-License-Identifier: BSD-3-Clause

namespace SingleA\Bundles\Redis\Tests\DependencyInjection\Compiler;

use PHPUnit\Framework\TestCase;
use SingleA\Bundles\Redis\DependencyInjection\Compiler\SncRedisClientPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;

/**
 * @covers \SingleA\Bundles\Redis\DependencyInjection\Compiler\SncRedisClientPass
 *
 * @internal
 */
final class SncRedisClientPassTest extends TestCase
{
    public function testSuccessfulProcess(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('singlea_redis.snc_redis_client_name', 'default');
        $container->setDefinition('snc_redis.default', new Definition());

        (new SncRedisClientPass())->process($container);

        self::assertTrue($container->hasAlias('singlea_redis.snc_redis_client'));
        self::assertSame('snc_redis.default', (string) $container->getAlias('singlea_redis.snc_redis_client'));
    }

    public function testInvalidSncClientName(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('singlea_redis.snc_redis_client_name', false);

        $pass = new SncRedisClientPass();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('SncRedis client name must be a string.');

        $pass->process($container);
    }

    public function testUnknownSncClient(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('singlea_redis.snc_redis_client_name', 'default');

        $pass = new SncRedisClientPass();

        $this->expectException(ServiceNotFoundException::class);
        $this->expectExceptionMessage('The SncRedis client with name "default" does not exists.');

        $pass->process($container);
    }
}
