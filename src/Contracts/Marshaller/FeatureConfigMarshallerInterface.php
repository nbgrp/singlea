<?php declare(strict_types=1);
// SPDX-License-Identifier: BSD-3-Clause

namespace SingleA\Contracts\Marshaller;

use SingleA\Contracts\FeatureConfig\FeatureConfigInterface;

/**
 * The service allows marshalling of client feature config.
 */
interface FeatureConfigMarshallerInterface
{
    /**
     * Does the marshaller work with the specified config.
     *
     * @param class-string<FeatureConfigInterface>|FeatureConfigInterface $config
     */
    public function supports(FeatureConfigInterface|string $config): bool;

    public function marshall(FeatureConfigInterface $config): string;

    public function unmarshall(string $value): FeatureConfigInterface;
}
