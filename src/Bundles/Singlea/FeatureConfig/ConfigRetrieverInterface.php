<?php declare(strict_types=1);
// SPDX-License-Identifier: BSD-3-Clause

namespace SingleA\Bundles\Singlea\FeatureConfig;

use SingleA\Contracts\FeatureConfig\FeatureConfigInterface;

/**
 * The service helps to find client feature configs by the config interface and the client id.
 */
interface ConfigRetrieverInterface
{
    /**
     * @param class-string<FeatureConfigInterface> $configInterface
     */
    public function exists(string $configInterface, string $clientId): bool;

    /**
     * @param class-string<FeatureConfigInterface> $configInterface
     */
    public function find(string $configInterface, string $clientId, string $secret): ?FeatureConfigInterface;
}
