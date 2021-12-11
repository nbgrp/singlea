<?php declare(strict_types=1);
// SPDX-License-Identifier: BSD-3-Clause

namespace SingleA\Bundles\JsonFetcher\FeatureConfig;

use SingleA\Contracts\PayloadFetcher\FetcherConfigFactoryInterface;

final class JsonFetcherConfigFactory implements FetcherConfigFactoryInterface
{
    public function __construct(
        private bool $httpsOnly,
    ) {}

    public function getConfigClass(): string
    {
        return JsonFetcherConfig::class;
    }

    public function getKey(): string
    {
        return self::KEY;
    }

    public function getHash(): string
    {
        return 'json';
    }

    /**
     * @psalm-suppress MixedArgument
     */
    public function create(array $input, mixed &$output = null): JsonFetcherConfig
    {
        $errors = array_filter([
            $this->validateEndpoint($input),
            $this->validateClaims($input),
            $this->validateRequestOptions($input),
        ]);

        if ($errors) {
            throw new \DomainException(implode("\n", $errors));
        }

        return new JsonFetcherConfig(
            $input['endpoint'],
            $input['claims'] ?? null,
            $input['options'] ?? null,
        );
    }

    private function makeInvalidMessage(string $subKey, string $message): string
    {
        return 'The "'.$this->getKey().'.'.$subKey.'" parameter '.$message.'.';
    }

    private function validateEndpoint(array $input): ?string
    {
        if (!\array_key_exists('endpoint', $input)) {
            return $this->makeInvalidMessage('endpoint', 'is required');
        }

        if (!\is_string($input['endpoint'])) {
            return $this->makeInvalidMessage('endpoint', 'must be a string');
        }

        $validScheme = $this->httpsOnly ? 'https' : 'http';
        if (!str_starts_with((string) parse_url($input['endpoint'], \PHP_URL_SCHEME), $validScheme)) {
            return $this->makeInvalidMessage('endpoint', 'must be an URL with '.$validScheme.' scheme');
        }

        return null;
    }

    private function validateClaims(array $input): ?string
    {
        if (!\array_key_exists('claims', $input)) {
            return null;
        }

        if (!\is_array($input['claims'])) {
            return $this->makeInvalidMessage('claims', 'must be an array');
        }

        return null;
    }

    private function validateRequestOptions(array $input): ?string
    {
        if (!\array_key_exists('options', $input)) {
            return null;
        }

        if (!\is_array($input['options'])) {
            return $this->makeInvalidMessage('options', 'must be an array');
        }

        return null;
    }
}
