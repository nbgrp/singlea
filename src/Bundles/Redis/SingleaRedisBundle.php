<?php
// SPDX-License-Identifier: BSD-3-Clause

declare(strict_types=1);

namespace SingleA\Bundles\Redis;

use SingleA\Bundles\Redis\DependencyInjection\Compiler\AddFeatureConfigManagersPass;
use SingleA\Bundles\Redis\DependencyInjection\Compiler\SncRedisClientPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * @final
 */
class SingleaRedisBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container
            ->addCompilerPass(new AddFeatureConfigManagersPass(), priority: 1000)
            ->addCompilerPass(new SncRedisClientPass())
        ;
    }
}
