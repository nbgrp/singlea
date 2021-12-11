<?php declare(strict_types=1);
// SPDX-License-Identifier: BSD-3-Clause

namespace SingleA\Contracts\FeatureConfig;

/**
 * The basic feature config interface. All feature implementations must use it in their configs.
 */
interface FeatureConfigInterface
{
    public function __serialize(): array;

    public function __unserialize(array $data): void;
}
