<?php declare(strict_types=1);
// SPDX-License-Identifier: BSD-3-Clause

namespace SingleA\Bundles\Singlea\Tests;

use PHPUnit\Framework\TestCase;
use SingleA\Bundles\Singlea\DependencyInjection\Compiler\CachePoolPass;
use SingleA\Bundles\Singlea\DependencyInjection\Compiler\RealmRequestMatcherPass;
use SingleA\Bundles\Singlea\SingleaBundle;
use SingleA\Contracts\FeatureConfig\FeatureConfigFactoryInterface;
use SingleA\Contracts\PayloadFetcher\FetcherInterface;
use SingleA\Contracts\Persistence\FeatureConfigManagerInterface;
use SingleA\Contracts\Tokenization\TokenizerInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @covers \SingleA\Bundles\Singlea\SingleaBundle
 *
 * @internal
 */
final class SingleaBundleTest extends TestCase
{
    public function testBuild(): void
    {
        $bundle = new SingleaBundle();
        $container = new ContainerBuilder();

        $bundle->build($container);
        $passes = $container->getCompilerPassConfig()->getBeforeOptimizationPasses();

        self::assertNotEmpty(array_filter($passes, static fn (CompilerPassInterface $pass): bool => $pass instanceof CachePoolPass));
        self::assertNotEmpty(array_filter($passes, static fn (CompilerPassInterface $pass): bool => $pass instanceof RealmRequestMatcherPass));

        $autoconfiguredInstanceof = $container->getAutoconfiguredInstanceof();

        self::assertArrayHasKey(FeatureConfigFactoryInterface::class, $autoconfiguredInstanceof);
        self::assertTrue($autoconfiguredInstanceof[FeatureConfigFactoryInterface::class]->hasTag('singlea.config_factory'));

        self::assertArrayHasKey(FeatureConfigManagerInterface::class, $autoconfiguredInstanceof);
        self::assertTrue($autoconfiguredInstanceof[FeatureConfigManagerInterface::class]->hasTag('singlea.config_manager'));

        self::assertArrayHasKey(TokenizerInterface::class, $autoconfiguredInstanceof);
        self::assertTrue($autoconfiguredInstanceof[TokenizerInterface::class]->hasTag('singlea.tokenizer'));

        self::assertArrayHasKey(FetcherInterface::class, $autoconfiguredInstanceof);
        self::assertTrue($autoconfiguredInstanceof[FetcherInterface::class]->hasTag('singlea.payload_fetcher'));
    }
}
