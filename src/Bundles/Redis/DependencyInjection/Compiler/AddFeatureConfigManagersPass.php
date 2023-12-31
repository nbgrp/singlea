<?php
// SPDX-License-Identifier: BSD-3-Clause

declare(strict_types=1);

namespace SingleA\Bundles\Redis\DependencyInjection\Compiler;

use Psr\Log\LoggerInterface;
use SingleA\Bundles\Redis\FeatureConfigManagerFactory;
use SingleA\Contracts\Marshaller\FeatureConfigEncryptorInterface;
use SingleA\Contracts\Persistence\FeatureConfigManagerInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

final class AddFeatureConfigManagersPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $configManagers = $container->getParameter('singlea_redis.config_managers');
        if (!\is_array($configManagers)) {
            return;
        }

        foreach ($configManagers as $name => $setting) {
            if (!\is_array($setting)) {
                throw new \RuntimeException(sprintf('Config manager "%s" settings must be present as an array.', $name));
            }

            $container->setDefinition(
                sprintf('singlea.feature_config_manager.%s', $name),
                (new Definition(FeatureConfigManagerInterface::class))
                    ->setAutoconfigured(true)
                    ->setFactory(new Reference(FeatureConfigManagerFactory::class))
                    ->setArguments([
                        (string) ($setting['key'] ?? throw new \UnderflowException(sprintf('Config manager "%s" settings has no key value.', $name))),
                        new Reference('singlea_redis.snc_redis_client'),
                        new Reference((string) ($setting['config_marshaller'] ?? throw new \UnderflowException(sprintf('Config manager "%s" settings has no config_marshaller value.', $name)))),
                        new Reference(FeatureConfigEncryptorInterface::class),
                        $setting['required'] ?? false,
                        new Reference(LoggerInterface::class, ContainerInterface::NULL_ON_INVALID_REFERENCE),
                    ]),
            );
        }
    }
}
