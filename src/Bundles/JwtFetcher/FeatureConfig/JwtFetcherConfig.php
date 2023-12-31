<?php
// SPDX-License-Identifier: BSD-3-Clause

declare(strict_types=1);

namespace SingleA\Bundles\JwtFetcher\FeatureConfig;

use SingleA\Contracts\PayloadFetcher\FetcherConfigInterface;

final class JwtFetcherConfig implements FetcherConfigInterface
{
    /**
     * @param list<string>|null $claims
     */
    public function __construct(
        private string $endpoint,
        private ?array $claims,
        private JwsConfig $requestJwsConfig,
        private ?JweConfig $requestJweConfig,
        private ?array $requestOptions,
        private JwsConfig $responseJwsConfig,
        private ?JweConfig $responseJweConfig,
    ) {}

    public function __serialize(): array
    {
        return [
            'e' => $this->endpoint,
            'c' => $this->claims,
            'qs' => $this->requestJwsConfig,
            'qe' => $this->requestJweConfig,
            'qo' => $this->requestOptions,
            'ps' => $this->responseJwsConfig,
            'pe' => $this->responseJweConfig,
        ];
    }

    /**
     * @param array{e: string, c: ?list<string>, qs: JwsConfig, qe: ?JweConfig, qo: ?array, ps: JwsConfig, pe: ?JweConfig} $data
     *
     * @psalm-suppress MoreSpecificImplementedParamType
     */
    public function __unserialize(array $data): void
    {
        $this->endpoint = $data['e'];
        $this->claims = $data['c'];
        $this->requestJwsConfig = $data['qs'];
        $this->requestJweConfig = $data['qe'];
        $this->requestOptions = $data['qo'];
        $this->responseJwsConfig = $data['ps'];
        $this->responseJweConfig = $data['pe'];
    }

    public function getEndpoint(): string
    {
        return $this->endpoint;
    }

    public function getClaims(): ?array
    {
        return $this->claims;
    }

    public function getRequestJwsConfig(): JwsConfig
    {
        return $this->requestJwsConfig;
    }

    public function getRequestJweConfig(): ?JweConfig
    {
        return $this->requestJweConfig;
    }

    public function getRequestOptions(): ?array
    {
        return $this->requestOptions;
    }

    public function getResponseJwsConfig(): JwsConfig
    {
        return $this->responseJwsConfig;
    }

    public function getResponseJweConfig(): ?JweConfig
    {
        return $this->responseJweConfig;
    }
}
