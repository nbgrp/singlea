<?php declare(strict_types=1);
// SPDX-License-Identifier: BSD-3-Clause

namespace SingleA\Bundles\Singlea\DependencyInjection\Compiler;

use SingleA\Bundles\Singlea\Service\UserAttributes\UserAttributesManagerInterface;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Check for user attributes cache existence for every realm (firewall) and adds missed caches.
 */
final class CachePoolPass implements CompilerPassInterface
{
    private const BASE_POOL = 'singlea.cache';
    private const FIREWALL_POOL_PREFIX = 'singlea.cache.';

    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasParameter('security.firewalls')) {
            return;
        }

        /** @var list<string> $firewalls */
        $firewalls = $container->getParameter('security.firewalls');
        $pools = self::getPools($container);

        $autoPoolFirewalls = array_diff($firewalls, array_keys($pools)); // @phan-suppress-current-line PhanPartialTypeMismatchArgumentInternal
        if (!empty($autoPoolFirewalls)) {
            $pools = array_merge($pools, self::addMissedFirewallPools($container, $autoPoolFirewalls));
        }

        $userAttributesManagerDefinition = $container->getDefinition(UserAttributesManagerInterface::class);
        $userAttributesManagerDefinition->replaceArgument(0, $pools);
    }

    /**
     * @return array<string, Reference>
     */
    private static function getPools(ContainerBuilder $container): array
    {
        $pools = [];
        foreach ($container->findTaggedServiceIds('cache.pool') as $id => $attributes) {
            $poolName = (string) ($attributes[0]['name'] ?? $id);
            if ($poolName === self::BASE_POOL || !str_starts_with($poolName, self::FIREWALL_POOL_PREFIX)) {
                continue;
            }

            $key = str_replace(self::FIREWALL_POOL_PREFIX, '', $poolName);
            $pools[$key] = new Reference($id);
        }

        return $pools;
    }

    /**
     * @param non-empty-array<int, string> $firewalls
     *
     * @return array<string, Reference>
     */
    private static function addMissedFirewallPools(ContainerBuilder $container, array $firewalls): array
    {
        if (!$container->hasDefinition(self::BASE_POOL)) {
            throw new ServiceNotFoundException(self::BASE_POOL, msg: 'Cache pool "'.self::BASE_POOL.'" must be defined in cache settings. It used as a parent for realm-specific (firewall-specific) user attributes cache pools.');
        }

        return array_map(static function (string $firewall) use ($container): Reference {
            $poolDefinition = new ChildDefinition(self::BASE_POOL);
            $poolDefinition->addTag('cache.pool');
            $poolId = self::FIREWALL_POOL_PREFIX.$firewall;
            $container->setDefinition($poolId, $poolDefinition);

            return new Reference($poolId);
        }, array_combine($firewalls, $firewalls));
    }
}
