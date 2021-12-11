<?php declare(strict_types=1);
// SPDX-License-Identifier: BSD-3-Clause

namespace SingleA\Bundles\Singlea\FeatureConfig\Signature;

use SingleA\Contracts\FeatureConfig\FeatureConfigFactoryInterface;

final class SignatureConfigFactory implements FeatureConfigFactoryInterface
{
    public function getConfigClass(): string
    {
        return SignatureConfig::class;
    }

    public function getKey(): string
    {
        return 'signature';
    }

    public function getHash(): string
    {
        return $this->getKey();
    }

    /**
     * @psalm-suppress MixedArgument
     */
    public function create(array $input, mixed &$output = null): SignatureConfig
    {
        $errors = array_filter([
            $this->validateMessageDigestAlgorithm($input),
            $this->validatePublicKey($input),
            $this->validateClientClockSkew($input),
        ]);

        if ($errors) {
            throw new \DomainException(implode("\n", $errors));
        }

        return new SignatureConfig(
            \constant('OPENSSL_ALGO_'.strtoupper($input['md-alg'])) ?? throw new \RuntimeException('Unknown OpenSSL message digest algorithm.'), // @phpstan-ignore-line
            $input['key'],
            $input['skew'] ?? 0,
        );
    }

    private function makeInvalidMessage(string $subKey, string $message): string
    {
        return 'The "'.$this->getKey().'.'.$subKey.'" parameter '.$message.'.';
    }

    private function validateMessageDigestAlgorithm(array $input): ?string
    {
        if (!\array_key_exists('md-alg', $input)) {
            return $this->makeInvalidMessage('md-alg', 'is required');
        }

        if (!\is_string($input['md-alg'])) {
            return $this->makeInvalidMessage('md-alg', 'must be a string');
        }

        $algorithmConstantName = 'OPENSSL_ALGO_'.strtoupper($input['md-alg']);
        if (!\defined($algorithmConstantName)) {
            return $this->makeInvalidMessage('md-alg', 'is invalid, unknown constant '.$algorithmConstantName);
        }

        return null;
    }

    private function validatePublicKey(array $input): ?string
    {
        if (!\array_key_exists('key', $input)) {
            return $this->makeInvalidMessage('key', 'is required');
        }

        if (!\is_string($input['key'])) {
            return $this->makeInvalidMessage('key', 'must be a string');
        }

        return null;
    }

    private function validateClientClockSkew(array $input): ?string
    {
        if (!\array_key_exists('skew', $input)) {
            return null;
        }

        if (!is_numeric($input['skew'])) {
            return $this->makeInvalidMessage('skew', 'must be a number');
        }

        return null;
    }
}
