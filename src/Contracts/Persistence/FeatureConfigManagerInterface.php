<?php declare(strict_types=1);
// SPDX-License-Identifier: BSD-3-Clause

namespace SingleA\Contracts\Persistence;

use SingleA\Contracts\FeatureConfig\FeatureConfigInterface;

/**
 * Feature config manager allows working with client feature configs on middle abstraction layer.
 *
 * It knows about different features, but doesn't distinguish their implementations. It allows
 * persisting the different implementations of the same config feature in the same storage.
 */
interface FeatureConfigManagerInterface
{
    /**
     * Does the manager work with the specified feature config.
     *
     * @param class-string<FeatureConfigInterface>|FeatureConfigInterface $config
     */
    public function supports(FeatureConfigInterface|string $config): bool;

    /**
     * Does this feature required (for the client registration).
     */
    public function isRequired(): bool;

    /**
     * Does client feature config exist for the specified id.
     */
    public function exists(string $id): bool;

    /**
     * Store client feature config for the specified id and secret.
     *
     * The secret should be used for the config encryption.
     */
    public function persist(string $id, FeatureConfigInterface $config, string $secret): void;

    /**
     * Get client feature config for the specified id and secret if exists.
     *
     * The secret should be used for the config decryption.
     */
    public function find(string $id, string $secret): ?FeatureConfigInterface;

    /**
     * Remove client feature configs by the specified ids and return the number of actually removed
     * configs.
     */
    public function remove(string ...$ids): int;
}
