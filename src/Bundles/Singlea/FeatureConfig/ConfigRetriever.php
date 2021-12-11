<?php declare(strict_types=1);
// SPDX-License-Identifier: BSD-3-Clause

namespace SingleA\Bundles\Singlea\FeatureConfig;

use SingleA\Contracts\FeatureConfig\FeatureConfigInterface;
use SingleA\Contracts\Persistence\FeatureConfigManagerInterface;

final class ConfigRetriever implements ConfigRetrieverInterface
{
    /**
     * @param iterable<FeatureConfigManagerInterface> $configManagers
     */
    public function __construct(
        private iterable $configManagers,
    ) {}

    public function exists(string $configInterface, string $clientId): bool
    {
        foreach ($this->configManagers as $configManager) {
            if (!$configManager->supports($configInterface)) {
                continue;
            }

            return $configManager->exists($clientId);
        }

        return false;
    }

    public function find(string $configInterface, string $clientId, string $secret): ?FeatureConfigInterface
    {
        foreach ($this->configManagers as $configManager) {
            if (!$configManager->supports($configInterface)) {
                continue;
            }

            $config = $configManager->find($clientId, $secret);
            if ($config && is_a($config, $configInterface)) {
                return $config;
            }
        }

        return null;
    }
}
