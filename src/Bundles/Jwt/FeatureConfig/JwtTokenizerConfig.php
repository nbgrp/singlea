<?php declare(strict_types=1);
// SPDX-License-Identifier: BSD-3-Clause

namespace SingleA\Bundles\Jwt\FeatureConfig;

use SingleA\Contracts\Tokenization\TokenizerConfigInterface;

final class JwtTokenizerConfig implements TokenizerConfigInterface
{
    /**
     * @param list<string>|null $claims
     */
    public function __construct(
        private ?int $ttl,
        private ?array $claims,
        private JwsConfig $jwsConfig,
        private ?JweConfig $jweConfig,
        private ?string $audience,
    ) {
        if (isset($this->ttl) && $this->ttl < 0) {
            throw new \DomainException('Negative token TTL.');
        }
    }

    public function __serialize(): array
    {
        return [
            't' => $this->ttl,
            'c' => $this->claims,
            's' => $this->jwsConfig,
            'e' => $this->jweConfig,
            'a' => $this->audience,
        ];
    }

    /**
     * @param array{t: ?int, c: ?list<string>, s: JwsConfig, e: ?JweConfig, a: ?string} $data
     *
     * @psalm-suppress MoreSpecificImplementedParamType
     */
    public function __unserialize(array $data): void
    {
        $this->ttl = $data['t'];
        $this->claims = $data['c'];
        $this->jwsConfig = $data['s'];
        $this->jweConfig = $data['e'];
        $this->audience = $data['a'];
    }

    public function getTtl(): ?int
    {
        return $this->ttl;
    }

    public function getClaims(): ?array
    {
        return $this->claims;
    }

    public function getJwsConfig(): JwsConfig
    {
        return $this->jwsConfig;
    }

    public function getJweConfig(): ?JweConfig
    {
        return $this->jweConfig;
    }

    public function getAudience(): ?string
    {
        return $this->audience;
    }
}
