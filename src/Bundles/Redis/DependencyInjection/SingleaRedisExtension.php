<?php
// SPDX-License-Identifier: BSD-3-Clause

declare(strict_types=1);

namespace SingleA\Bundles\Redis\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

/**
 * @internal
 * @final
 */
class SingleaRedisExtension extends Extension
{
    /**
     * @psalm-suppress MixedArgument
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new PhpFileLoader($container, new FileLocator(\dirname(__DIR__).'/Resources/config'));
        $loader->load('services.php');

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('singlea_redis.client_last_access_key', $config['client_last_access_key']);
        $container->setParameter('singlea_redis.snc_redis_client_name', $config['snc_redis_client']);
        $container->setParameter('singlea_redis.config_managers', $config['config_managers'] ?? []);
    }
}
