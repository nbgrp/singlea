<?php declare(strict_types=1);
// SPDX-License-Identifier: BSD-3-Clause

namespace SingleA\Contracts\PayloadFetcher;

use SingleA\Contracts\FeatureConfig\FeatureConfigFactoryInterface;

interface FetcherConfigFactoryInterface extends FeatureConfigFactoryInterface
{
    public const KEY = 'payload';

    public function create(array $input, mixed &$output = null): FetcherConfigInterface;
}
