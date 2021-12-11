<?php declare(strict_types=1);
// SPDX-License-Identifier: BSD-3-Clause

namespace SingleA\Bundles\JwtFetcher\Tests\DependencyInjection;

use PHPUnit\Framework\TestCase;
use SingleA\Bundles\JwtFetcher\DependencyInjection\SingleaJwtFetcherExtension;
use SingleA\Bundles\JwtFetcher\FeatureConfig\JwtFetcherConfigFactory;
use SingleA\Bundles\JwtFetcher\JwtFetcher;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @covers \SingleA\Bundles\JwtFetcher\DependencyInjection\Configuration
 * @covers \SingleA\Bundles\JwtFetcher\DependencyInjection\SingleaJwtFetcherExtension
 *
 * @internal
 */
final class SingleaJwtFetcherExtensionTest extends TestCase
{
    public function testLoad(): void
    {
        $container = new ContainerBuilder();
        $extension = new SingleaJwtFetcherExtension();

        $extension->load([], $container);

        self::assertTrue($container->hasDefinition(JwtFetcher::class));
        self::assertTrue($container->hasDefinition(JwtFetcherConfigFactory::class));

        self::assertTrue($container->hasParameter('singlea_jwt_fetcher.https_only'));
    }
}
