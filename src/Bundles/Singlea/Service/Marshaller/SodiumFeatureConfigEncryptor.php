<?php declare(strict_types=1);
// SPDX-License-Identifier: BSD-3-Clause

namespace SingleA\Bundles\Singlea\Service\Marshaller;

use SingleA\Contracts\Marshaller\FeatureConfigEncryptorInterface;

final class SodiumFeatureConfigEncryptor implements FeatureConfigEncryptorInterface
{
    /** @var non-empty-list<string> */
    private array $keys;

    public function __construct(
        mixed $keys,
    ) {
        if (!\is_array($keys)) {
            throw new \InvalidArgumentException('Client keys must be provided as an array.');
        }

        if (empty($keys)) {
            throw new \InvalidArgumentException('At least one key must be provided.');
        }

        /** @psalm-suppress MixedArgument */
        $this->keys = array_values(array_map('strval', $keys));
    }

    public function encrypt(string $value, string $secret): string
    {
        return sodium_crypto_secretbox($value, $secret, $this->keys[0]);
    }

    public function decrypt(string $value, string $secret): string
    {
        foreach ($this->keys as $key) {
            $decrypted = sodium_crypto_secretbox_open($value, $secret, $key);
            if ($decrypted !== false) {
                return $decrypted;
            }
        }

        throw new \RuntimeException('Cannot decrypt value.');
    }
}
