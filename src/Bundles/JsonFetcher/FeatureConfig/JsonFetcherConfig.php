<?php declare(strict_types=1);
// SPDX-License-Identifier: BSD-3-Clause

namespace SingleA\Bundles\JsonFetcher\FeatureConfig;

use SingleA\Contracts\PayloadFetcher\FetcherConfigInterface;

final class JsonFetcherConfig implements FetcherConfigInterface
{
    /**
     * @param list<string>|null $claims
     */
    public function __construct(
        private string $endpoint,
        private ?array $claims,
        private ?array $requestOptions,
    ) {}

    public function __serialize(): array
    {
        return [
            'e' => $this->endpoint,
            'c' => $this->claims,
            'o' => $this->requestOptions,
        ];
    }

    /**
     * @param array{e: string, c: ?list<string>, o: ?array} $data
     *
     * @psalm-suppress MoreSpecificImplementedParamType
     */
    public function __unserialize(array $data): void
    {
        $this->endpoint = $data['e'];
        $this->claims = $data['c'];
        $this->requestOptions = $data['o'];
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
