<?php
// SPDX-License-Identifier: BSD-3-Clause

declare(strict_types=1);

namespace SingleA\Contracts\Tokenization;

/**
 * Tokenizer allows generating user token according client tokenization config.
 */
interface TokenizerInterface
{
    /**
     * Does the tokenizer work with specified config.
     */
    public function supports(string|TokenizerConfigInterface $config): bool;

    /**
     * Generate user token with the specified payload according the specified config.
     *
     * @param string $subject The token subject (usually the user identifier)
     */
    public function tokenize(string $subject, array $payload, TokenizerConfigInterface $config): string;
}
