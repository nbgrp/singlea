<?php declare(strict_types=1);
// SPDX-License-Identifier: BSD-3-Clause

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use SingleA\Bundles\Redis;
use SingleA\Contracts;

return static function (ContainerConfigurator $container): void {
    $container->services()
        ->set(Redis\FeatureConfigManagerFactory::class)

        ->set(Contracts\Persistence\ClientManagerInterface::class, Redis\ClientManager::class)
            ->args([
                param('singlea_redis.client_last_access_key'),
                service('singlea_redis.snc_redis_client'),
                service(\Psr\Log\LoggerInterface::class)->nullOnInvalid(),
            ])
    ;
};
