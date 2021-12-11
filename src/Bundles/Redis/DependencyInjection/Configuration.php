<?php declare(strict_types=1);
// SPDX-License-Identifier: BSD-3-Clause

namespace SingleA\Bundles\Redis\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * @final
 */
class Configuration implements ConfigurationInterface
{
    /**
     * @suppress PhanPossiblyNonClassMethodCall, PhanPossiblyUndeclaredMethod
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('singlea_redis');
        $rootNode = $treeBuilder->getRootNode();

        // @formatter:off
        /** @phpstan-ignore-next-line */
        $rootNode
            ->info('SingleA Redis Bundle configuration')
            ->addDefaultsIfNotSet()
            ->children()
                ->scalarNode('client_last_access_key')
                    ->cannotBeEmpty()
                    ->defaultValue('singlea:last-access')
                ->end()
                ->scalarNode('snc_redis_client')
                    ->cannotBeEmpty()
                    ->defaultValue('default')
                    ->validate()
                        ->ifTrue(static fn ($value): bool => !\is_string($value))
                        ->thenInvalid('SncRedis client name must be a string.')
                    ->end()
                ->end()
                ->arrayNode('config_managers')
                    ->fixXmlConfig('config_manager')
                    ->useAttributeAsKey('name')
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('key')
                                ->cannotBeEmpty()
                            ->end()
                            ->scalarNode('config_marshaller')
                                ->cannotBeEmpty()
                            ->end()
                            ->booleanNode('required')
                                ->defaultFalse()
                                ->treatNullLike(false)
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;
        // @formatter:on

        return $treeBuilder;
    }
}
