<?php declare(strict_types=1);
// SPDX-License-Identifier: BSD-3-Clause

namespace SingleA\Bundles\Singlea\FeatureConfig\Signature;

use SingleA\Contracts\FeatureConfig\FeatureConfigInterface;

interface SignatureConfigInterface extends FeatureConfigInterface
{
    /**
     * The message digest algorithm used to verify the request signature.
     */
    public function getMessageDigestAlgorithm(): int;

    /**
     * The public key used to verify the request signature.
     */
    public function getPublicKey(): string;

    /**
     * Skew between SingleA's system clock and the client's.
     */
    public function getClientClockSkew(): int;
}
