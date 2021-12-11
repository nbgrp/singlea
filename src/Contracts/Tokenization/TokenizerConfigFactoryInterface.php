<?php declare(strict_types=1);
// SPDX-License-Identifier: BSD-3-Clause

namespace SingleA\Contracts\Tokenization;

use SingleA\Contracts\FeatureConfig\FeatureConfigFactoryInterface;

interface TokenizerConfigFactoryInterface extends FeatureConfigFactoryInterface
{
    final public const KEY = 'token';

    public function create(array $input, mixed &$output = null): TokenizerConfigInterface;
}
