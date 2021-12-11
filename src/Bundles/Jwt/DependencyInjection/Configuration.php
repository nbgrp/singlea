<?php declare(strict_types=1);
// SPDX-License-Identifier: BSD-3-Clause

namespace SingleA\Bundles\Jwt\DependencyInjection;

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
        $treeBuilder = new TreeBuilder('singlea_jwt');
        $rootNode = $treeBuilder->getRootNode();

        // @formatter:off
        /** @phpstan-ignore-next-line */
        $rootNode
            ->info('SingleA JWT Bundle configuration')
            ->children()
                ->integerNode('default_token_ttl')
                    ->defaultValue(600)
                    ->validate()
                        ->ifTrue(static fn ($value): bool => $value < 0)
                        ->thenInvalid('Default token TTL should be a positive number or zero.')
                    ->end()
                ->end()
                ->scalarNode('jwt_issuer')
                    ->defaultNull()
                ->end()
            ->end()
        ;
        // @formatter:on

        return $treeBuilder;
    }
}
