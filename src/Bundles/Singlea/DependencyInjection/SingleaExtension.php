<?php
// SPDX-License-Identifier: BSD-3-Clause

declare(strict_types=1);

namespace SingleA\Bundles\Singlea\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

/**
 * @internal
 * @final
 */
class SingleaExtension extends Extension
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

        self::setParameters($container, 'singlea.client', $config['client']);
        self::setParameters($container, 'singlea.ticket', $config['ticket']);
        self::setParameters($container, 'singlea.authentication', $config['authentication']);
        self::setParameters($container, 'singlea.signature', $config['signature']);
        self::setParameters($container, 'singlea.encryption', $config['encryption']);
        self::setParameters($container, 'singlea.realm', $config['realm']);
        self::setParameters($container, 'singlea.marshaller', $config['marshaller']);
        self::setParameters($container, 'singlea.user_attributes', $config['user_attributes']);
    }

    /**
     * @psalm-suppress MixedAssignment, MixedArgument
     */
    private static function setParameters(ContainerBuilder $container, string $prefix, array $parameters): void
    {
        foreach ($parameters as $key => $value) {
            $container->setParameter(sprintf('%s.%s', $prefix, $key), $value);
        }
    }
}
