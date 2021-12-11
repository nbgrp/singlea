<?php declare(strict_types=1);
// SPDX-License-Identifier: BSD-3-Clause

namespace SingleA\Bundles\Singlea\Service\UserAttributes;

/**
 * The service allows marshalling of user attributes with usage of the ticket for the data
 * encryption/decryption.
 */
interface UserAttributesMarshallerInterface
{
    public function marshall(array $attributes, string $ticket): string;

    public function unmarshall(string $value, string $ticket): array;
}
