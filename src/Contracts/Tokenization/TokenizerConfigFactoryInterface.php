<?php
// SPDX-License-Identifier: BSD-3-Clause

declare(strict_types=1);

namespace SingleA\Contracts\Tokenization;

use SingleA\Contracts\FeatureConfig\FeatureConfigFactoryInterface;

interface TokenizerConfigFactoryInterface extends FeatureConfigFactoryInterface
{
    final public const KEY = 'token';

    public function create(array $input, mixed &$output = null): TokenizerConfigInterface;
}
