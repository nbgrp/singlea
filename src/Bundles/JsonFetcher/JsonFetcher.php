<?php
// SPDX-License-Identifier: BSD-3-Clause

declare(strict_types=1);

namespace SingleA\Bundles\JsonFetcher;

use SingleA\Bundles\JsonFetcher\FeatureConfig\JsonFetcherConfig;
use SingleA\Contracts\PayloadFetcher\FetcherConfigInterface;
use SingleA\Contracts\PayloadFetcher\FetcherInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final readonly class JsonFetcher implements FetcherInterface
{
    public function __construct(
        private HttpClientInterface $httpClient,
    ) {}

    public function supports(FetcherConfigInterface|string $config): bool
    {
        return is_a($config, JsonFetcherConfig::class, true);
    }

    /**
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     * @throws \JsonException
     */
    public function fetch(array $requestData, FetcherConfigInterface $config): array
    {
        if (!($config instanceof JsonFetcherConfig)) {
            throw new \InvalidArgumentException('Unsupported config specified.');
        }

        $options = $config->getRequestOptions() ?? [];
        $options['json'] = $requestData;

        return $this->httpClient
            ->request('POST', $config->getEndpoint(), $options)
            ->toArray()
        ;
    }
}
