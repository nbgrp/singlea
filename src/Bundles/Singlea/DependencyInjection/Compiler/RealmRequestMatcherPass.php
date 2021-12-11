<?php declare(strict_types=1);
// SPDX-License-Identifier: BSD-3-Clause

namespace SingleA\Bundles\Singlea\DependencyInjection\Compiler;

use SingleA\Bundles\Singlea\Request\RealmRequestMatcher;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

/**
 * Adds a realm request matcher for each existing firewall.
 */
final class RealmRequestMatcherPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasParameter('security.firewalls')) {
            return;
        }

        /** @var list<string> $firewalls */
        $firewalls = $container->getParameter('security.firewalls');

        foreach ($firewalls as $firewall) {
            $requestMatcherId = RealmRequestMatcher::for($firewall);

            $requestMatcherDefinition = new Definition(RealmRequestMatcher::class, [$firewall]);
            $container->setDefinition($requestMatcherId, $requestMatcherDefinition);
        }
    }
}
