<?php declare(strict_types=1);
// SPDX-License-Identifier: BSD-3-Clause

namespace SingleA\Bundles\Singlea\FeatureConfig\Signature;

final class SignatureConfig implements SignatureConfigInterface
{
    public function __construct(
        private int $messageDigestAlgorithm,
        private string $publicKey,
        private int $clientClockSkew,
    ) {}

    public function __serialize(): array
    {
        return [
            'a' => $this->messageDigestAlgorithm,
            'k' => $this->publicKey,
            's' => $this->clientClockSkew,
        ];
    }

    /**
     * @param array{a: int, k: string, s: int} $data
     *
     * @psalm-suppress MoreSpecificImplementedParamType
     */
    public function __unserialize(array $data): void
    {
        $this->messageDigestAlgorithm = $data['a'];
        $this->publicKey = $data['k'];
        $this->clientClockSkew = $data['s'];
    }

    public function getMessageDigestAlgorithm(): int
    {
        return $this->messageDigestAlgorithm;
    }

    public function getPublicKey(): string
    {
        return $this->publicKey;
    }

    public function getClientClockSkew(): int
    {
        return $this->clientClockSkew;
    }
}
