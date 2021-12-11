<?php declare(strict_types=1);
// SPDX-License-Identifier: BSD-3-Clause

namespace SingleA\Bundles\JsonFetcher\DependencyInjection;

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
        $treeBuilder = new TreeBuilder('singlea_json_fetcher');
        $rootNode = $treeBuilder->getRootNode();

        // @formatter:off
        /** @phpstan-ignore-next-line */
        $rootNode
            ->info('SingleA JSON Fetcher Bundle configuration')
            ->children()
                ->booleanNode('https_only')
                    ->defaultTrue()
                    ->treatNullLike(true)
                ->end()
            ->end()
        ;
        // @formatter:on

        return $treeBuilder;
    }
}
