<?php declare(strict_types=1);
// SPDX-License-Identifier: BSD-3-Clause

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Jose\Bundle\JoseFramework;
use SingleA\Bundles\JwtFetcher;

return static function (ContainerConfigurator $container): void {
    $container->services()
        ->defaults()
            ->autoconfigure()

        ->set(JwtFetcher\JwtFetcher::class)
            ->args([
                service(\Symfony\Contracts\HttpClient\HttpClientInterface::class),
                service(JoseFramework\Services\JWSBuilderFactory::class),
                service(JoseFramework\Services\NestedTokenBuilderFactory::class),
                service(JoseFramework\Services\JWSLoaderFactory::class),
                service(JoseFramework\Services\NestedTokenLoaderFactory::class),
            ])

        ->set(JwtFetcher\FeatureConfig\JwtFetcherConfigFactory::class)
            ->args([
                param('singlea_jwt_fetcher.https_only'),
                service(\Jose\Component\Core\AlgorithmManagerFactory::class),
                service(\Jose\Component\Encryption\Compression\CompressionMethodManagerFactory::class),
            ])
    ;
};
