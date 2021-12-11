<?php declare(strict_types=1);
// SPDX-License-Identifier: BSD-3-Clause

namespace SingleA\Bundles\Singlea\Tests\Service;

use SingleA\Contracts\Tokenization\TokenizerConfigInterface;

final class TestTokenizerConfig implements TokenizerConfigInterface
{
    public function __construct(
        private ?int $ttl,
        private ?array $claims,
    ) {}

    public function __serialize(): array
    {
        return [
            $this->ttl,
            $this->claims,
        ];
    }

    public function __unserialize(array $data): void
    {
        [
            $this->ttl,
            $this->claims,
        ] = $data;
    }

    public function getTtl(): ?int
    {
        return $this->ttl;
    }

    public function getClaims(): ?array
    {
        return $this->claims;
    }
}
