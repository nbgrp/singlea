<?php
// SPDX-License-Identifier: BSD-3-Clause

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use SingleA\Bundles\JsonFetcher;

return static function (ContainerConfigurator $container): void {
    $container->services()
        ->defaults()
            ->autoconfigure()

        ->set(JsonFetcher\JsonFetcher::class)
            ->args([
                service(\Symfony\Contracts\HttpClient\HttpClientInterface::class),
            ])

        ->set(JsonFetcher\FeatureConfig\JsonFetcherConfigFactory::class)
            ->args([
                param('singlea_json_fetcher.https_only'),
            ])
    ;
};
