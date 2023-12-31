<?php
// SPDX-License-Identifier: BSD-3-Clause

declare(strict_types=1);

namespace SingleA\Bundles\Jwt\Tests\DependencyInjection;

use PHPUnit\Framework\TestCase;
use SingleA\Bundles\Jwt\DependencyInjection\SingleaJwtExtension;
use SingleA\Bundles\Jwt\FeatureConfig\JwtTokenizerConfigFactory;
use SingleA\Bundles\Jwt\JwtTokenizer;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @covers \SingleA\Bundles\Jwt\DependencyInjection\Configuration
 * @covers \SingleA\Bundles\Jwt\DependencyInjection\SingleaJwtExtension
 *
 * @internal
 */
final class SingleaJwtExtensionTest extends TestCase
{
    public function testLoad(): void
    {
        $container = new ContainerBuilder();
        $extension = new SingleaJwtExtension();

        $extension->load([
            'singlea_jwt' => [
                'issuer' => 'https://sso.domain.org/',
            ],
        ], $container);

        self::assertTrue($container->hasDefinition(JwtTokenizer::class));
        self::assertTrue($container->hasDefinition(JwtTokenizerConfigFactory::class));

        self::assertTrue($container->hasParameter('singlea_jwt.config_default_ttl'));
        self::assertTrue($container->hasParameter('singlea_jwt.issuer'));
    }
}
