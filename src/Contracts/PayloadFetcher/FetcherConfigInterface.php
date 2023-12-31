<?php
// SPDX-License-Identifier: BSD-3-Clause

declare(strict_types=1);

namespace SingleA\Contracts\PayloadFetcher;

use SingleA\Contracts\FeatureConfig\FeatureConfigInterface;

interface FetcherConfigInterface extends FeatureConfigInterface
{
    /**
     * The URI of the payload endpoint to which the request will be sent.
     */
    public function getEndpoint(): string;

    /**
     * User attributes to be included in the request payload.
     *
     * @return list<string>|null
     */
    public function getClaims(): ?array;

    /**
     * Additional options for the HTTP client which will be used for the request.
     */
    public function getRequestOptions(): ?array;
}
