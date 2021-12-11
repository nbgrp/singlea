<?php declare(strict_types=1);
// SPDX-License-Identifier: BSD-3-Clause

namespace SingleA\Bundles\Redis\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;

final class SncRedisClientPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $sncClientName = $container->getParameter('singlea_redis.snc_redis_client_name');
        if (!\is_string($sncClientName)) {
            throw new \RuntimeException('SncRedis client name must be a string.');
        }

        $sncClientId = 'snc_redis.'.$sncClientName;

        if (!$container->hasDefinition($sncClientId)) {
            throw new ServiceNotFoundException($sncClientId, msg: 'The SncRedis client with name "'.$sncClientName.'" does not exists.');
        }

        $container->setAlias('singlea_redis.snc_redis_client', $sncClientId);
    }
}
