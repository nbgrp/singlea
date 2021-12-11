<?php declare(strict_types=1);
// SPDX-License-Identifier: BSD-3-Clause

namespace SingleA\Bundles\Redis\Tests;

use PHPUnit\Framework\TestCase;
use SingleA\Bundles\Redis\DependencyInjection\Compiler\AddFeatureConfigManagersPass;
use SingleA\Bundles\Redis\DependencyInjection\Compiler\SncRedisClientPass;
use SingleA\Bundles\Redis\SingleaRedisBundle;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @covers \SingleA\Bundles\Redis\SingleaRedisBundle
 *
 * @internal
 */
final class SingleaRedisBundleTest extends TestCase
{
    public function testBuild(): void
    {
        $bundle = new SingleaRedisBundle();
        $container = new ContainerBuilder();

        $bundle->build($container);
        $passes = $container->getCompilerPassConfig()->getBeforeOptimizationPasses();

        self::assertNotEmpty(array_filter($passes, static fn (CompilerPassInterface $pass): bool => $pass instanceof AddFeatureConfigManagersPass));
        self::assertNotEmpty(array_filter($passes, static fn (CompilerPassInterface $pass): bool => $pass instanceof SncRedisClientPass));
    }
}
