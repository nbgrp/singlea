<?php
// SPDX-License-Identifier: BSD-3-Clause

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Jose\Bundle\JoseFramework;
use SingleA\Bundles\Jwt;

return static function (ContainerConfigurator $container): void {
    $container->services()
        ->defaults()
            ->autoconfigure()

        ->set(Jwt\JwtTokenizer::class)
            ->args([
                param('singlea_jwt.issuer'),
                service(JoseFramework\Services\JWSBuilderFactory::class),
                service(JoseFramework\Services\NestedTokenBuilderFactory::class),
            ])

        ->set(Jwt\FeatureConfig\JwtTokenizerConfigFactory::class)
            ->args([
                param('singlea_jwt.config_default_ttl'),
                service(\Jose\Component\Core\AlgorithmManagerFactory::class),
                service(\Jose\Component\Encryption\Compression\CompressionMethodManagerFactory::class),
            ])
    ;
};
