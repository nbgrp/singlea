<?php
// SPDX-License-Identifier: BSD-3-Clause

declare(strict_types=1);

namespace SingleA\Contracts\Tokenization;

use SingleA\Contracts\FeatureConfig\FeatureConfigInterface;

interface TokenizerConfigInterface extends FeatureConfigInterface
{
    /**
     * Token lifetime in seconds.
     */
    public function getTtl(): ?int;

    /**
     * User attributes to be included in the token payload.
     *
     * @return list<string>|null
     */
    public function getClaims(): ?array;
}
