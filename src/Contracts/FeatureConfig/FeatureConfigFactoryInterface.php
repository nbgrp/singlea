<?php declare(strict_types=1);
// SPDX-License-Identifier: BSD-3-Clause

namespace SingleA\Contracts\FeatureConfig;

/**
 * Feature config factory allows creating of the concrete feature config implementations from
 * the client request data.
 */
interface FeatureConfigFactoryInterface
{
    /**
     * @return class-string<FeatureConfigInterface>
     */
    public function getConfigClass(): string;

    /**
     * Returns the key in the input (registration) data which value will be used to create
     * the config.
     */
    public function getKey(): string;

    /**
     * Returns the hash which will be compared with the "#" value from the input (registration) data
     * to select the certain implementation of the feature (config factory).
     *
     * Allows using a few implementations of the same feature at the same time.
     * By default, the hash value must be equal to the key.
     */
    public function getHash(): string;

    /**
     * Create a feature config based on the input (registration) data.
     *
     * @param mixed|null $output Output data (e.g. to output any data generated during the creation of the config)
     */
    public function create(array $input, mixed &$output = null): FeatureConfigInterface;
}
