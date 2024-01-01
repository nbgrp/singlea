<?php
// SPDX-License-Identifier: BSD-3-Clause

declare(strict_types=1);

namespace SingleA\Bundles\JsonFetcher\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * @final
 */
class Configuration implements ConfigurationInterface
{
    /**
     * @suppress PhanPossiblyUndeclaredMethod
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
                ->end()
            ->end()
        ;
        // @formatter:on

        return $treeBuilder;
    }
}
