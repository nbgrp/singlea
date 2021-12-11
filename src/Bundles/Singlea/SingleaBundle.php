<?php declare(strict_types=1);
// SPDX-License-Identifier: BSD-3-Clause

namespace SingleA\Bundles\Singlea;

use SingleA\Bundles\Singlea\DependencyInjection\Compiler\CachePoolPass;
use SingleA\Bundles\Singlea\DependencyInjection\Compiler\RealmRequestMatcherPass;
use SingleA\Contracts\FeatureConfig\FeatureConfigFactoryInterface;
use SingleA\Contracts\PayloadFetcher\FetcherInterface;
use SingleA\Contracts\Persistence\FeatureConfigManagerInterface;
use SingleA\Contracts\Tokenization\TokenizerInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * @final
 */
class SingleaBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->registerForAutoconfiguration(FeatureConfigFactoryInterface::class)
            ->addTag('singlea.config_factory')
        ;
        $container->registerForAutoconfiguration(FeatureConfigManagerInterface::class)
            ->addTag('singlea.config_manager')
        ;
        $container->registerForAutoconfiguration(TokenizerInterface::class)
            ->addTag('singlea.tokenizer')
        ;
        $container->registerForAutoconfiguration(FetcherInterface::class)
            ->addTag('singlea.payload_fetcher')
        ;

        $container
            ->addCompilerPass(new CachePoolPass())
            ->addCompilerPass(new RealmRequestMatcherPass())
        ;
    }
}
