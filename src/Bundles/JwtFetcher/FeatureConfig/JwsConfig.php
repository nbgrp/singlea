<?php
// SPDX-License-Identifier: BSD-3-Clause

declare(strict_types=1);

namespace SingleA\Bundles\JwtFetcher\FeatureConfig;

use Jose\Component\Core\JWK;

final class JwsConfig
{
    public function __construct(
        private string $algorithm,
        private JWK $jwk,
    ) {}

    public function __serialize(): array
    {
        return [
            'a' => $this->algorithm,
            'k' => $this->jwk,
        ];
    }

    /**
     * @param array{a: string, k: JWK} $data
     */
    public function __unserialize(array $data): void
    {
        $this->algorithm = $data['a'];
        $this->jwk = $data['k'];
    }

    public function getAlgorithm(): string
    {
        return $this->algorithm;
    }

    public function getJwk(): JWK
    {
        return $this->jwk;
    }
}
