<?php
// SPDX-License-Identifier: BSD-3-Clause

declare(strict_types=1);

namespace SingleA\Bundles\Singlea\Service\Client;

use Symfony\Component\Uid\UuidV6;

final readonly class RegistrationResult
{
    public function __construct(
        private UuidV6 $clientId,
        private string $secret,
        private array $output,
    ) {}

    public function getClientId(): UuidV6
    {
        return $this->clientId;
    }

    public function getSecret(): string
    {
        return $this->secret;
    }

    public function getOutput(): array
    {
        return $this->output;
    }
}
