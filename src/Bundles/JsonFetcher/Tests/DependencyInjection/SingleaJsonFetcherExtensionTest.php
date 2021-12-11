<?php declare(strict_types=1);
// SPDX-License-Identifier: BSD-3-Clause

namespace SingleA\Bundles\JsonFetcher\Tests\DependencyInjection;

use PHPUnit\Framework\TestCase;
use SingleA\Bundles\JsonFetcher\DependencyInjection\SingleaJsonFetcherExtension;
use SingleA\Bundles\JsonFetcher\FeatureConfig\JsonFetcherConfigFactory;
use SingleA\Bundles\JsonFetcher\JsonFetcher;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @covers \SingleA\Bundles\JsonFetcher\DependencyInjection\Configuration
 * @covers \SingleA\Bundles\JsonFetcher\DependencyInjection\SingleaJsonFetcherExtension
 *
 * @internal
 */
final class SingleaJsonFetcherExtensionTest extends TestCase
{
    public function testLoad(): void
    {
        $container = new ContainerBuilder();
        $extension = new SingleaJsonFetcherExtension();

        $extension->load([], $container);

        self::assertTrue($container->hasDefinition(JsonFetcher::class));
        self::assertTrue($container->hasDefinition(JsonFetcherConfigFactory::class));

        self::assertTrue($container->hasParameter('singlea_json_fetcher.https_only'));
    }
}
