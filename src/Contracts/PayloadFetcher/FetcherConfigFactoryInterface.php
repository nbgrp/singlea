<?php
// SPDX-License-Identifier: BSD-3-Clause

declare(strict_types=1);

namespace SingleA\Contracts\PayloadFetcher;

use SingleA\Contracts\FeatureConfig\FeatureConfigFactoryInterface;

interface FetcherConfigFactoryInterface extends FeatureConfigFactoryInterface
{
    final public const KEY = 'payload';

    public function create(array $input, mixed &$output = null): FetcherConfigInterface;
}
