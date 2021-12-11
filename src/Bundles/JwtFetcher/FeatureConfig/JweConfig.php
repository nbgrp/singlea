<?php declare(strict_types=1);
// SPDX-License-Identifier: BSD-3-Clause

namespace SingleA\Bundles\JwtFetcher\FeatureConfig;

use Jose\Component\Core\JWK;

final class JweConfig
{
    public function __construct(
        private string $keyAlgorithm,
        private string $contentAlgorithm,
        private ?string $compression,
        private JWK $recipientJwk,
    ) {}

    public function __serialize(): array
    {
        return [
            'a' => $this->keyAlgorithm,
            'c' => $this->contentAlgorithm,
            'z' => $this->compression,
            'k' => $this->recipientJwk,
        ];
    }

    /**
     * @param array{a: string, c: string, z: ?string, k: JWK} $data
     */
    public function __unserialize(array $data): void
    {
        $this->keyAlgorithm = $data['a'];
        $this->contentAlgorithm = $data['c'];
        $this->compression = $data['z'];
        $this->recipientJwk = $data['k'];
    }

    public function getKeyAlgorithm(): string
    {
        return $this->keyAlgorithm;
    }

    public function getContentAlgorithm(): string
    {
        return $this->contentAlgorithm;
    }

    public function getCompression(): ?string
    {
        return $this->compression;
    }

    public function getRecipientJwk(): JWK
    {
        return $this->recipientJwk;
    }
}
