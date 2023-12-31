<?php
// SPDX-License-Identifier: BSD-3-Clause

declare(strict_types=1);

namespace SingleA\Bundles\Singlea\Service\Tokenization;

use SingleA\Contracts\PayloadFetcher\FetcherConfigInterface;
use SingleA\Contracts\Tokenization\TokenizerConfigInterface;

/**
 * The service makes all the work of calculation the user token payload.
 */
interface PayloadComposerInterface
{
    public function compose(array $userAttributes, TokenizerConfigInterface $tokenizerConfig, ?FetcherConfigInterface $fetcherConfig): array;
}
