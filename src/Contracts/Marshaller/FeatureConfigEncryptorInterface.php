<?php declare(strict_types=1);
// SPDX-License-Identifier: BSD-3-Clause

namespace SingleA\Contracts\Marshaller;

/**
 * The service allows encrypting of a (marshalled) client feature config with a secret known only
 * to the client.
 */
interface FeatureConfigEncryptorInterface
{
    public function encrypt(string $value, string $secret): string;

    public function decrypt(string $value, string $secret): string;
}
