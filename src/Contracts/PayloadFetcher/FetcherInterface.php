<?php declare(strict_types=1);
// SPDX-License-Identifier: BSD-3-Clause

namespace SingleA\Contracts\PayloadFetcher;

/**
 * The service allows fetching additional payload for the user token from the external source
 * (via HTTP request).
 */
interface FetcherInterface
{
    /**
     * Does the fetcher work with the specified config.
     */
    public function supports(FetcherConfigInterface|string $config): bool;

    /**
     * Makes a request with the specified data according the specified config and returns received
     * additional payload.
     */
    public function fetch(array $requestData, FetcherConfigInterface $config): array;
}
