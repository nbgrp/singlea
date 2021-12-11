<?php declare(strict_types=1);
// SPDX-License-Identifier: BSD-3-Clause

namespace SingleA\Bundles\Singlea\Tests\Service;

use SingleA\Contracts\PayloadFetcher\FetcherConfigInterface;

final class TestFetcherConfig implements FetcherConfigInterface
{
    public function __construct(
        private string $endpoint,
        private ?array $claims,
        private ?array $requestOptions,
    ) {}

    public function __serialize(): array
    {
        return [
            $this->endpoint,
            $this->claims,
            $this->requestOptions,
        ];
    }

    public function __unserialize(array $data): void
    {
        [
            $this->endpoint,
            $this->claims,
            $this->requestOptions,
        ] = $data;
    }

    public function getEndpoint(): string
    {
        return $this->endpoint;
    }

    public function getClaims(): ?array
    {
        return $this->claims;
    }

    public function getRequestOptions(): ?array
    {
        return $this->requestOptions;
    }
}
