<?php
// SPDX-License-Identifier: BSD-3-Clause

declare(strict_types=1);

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
