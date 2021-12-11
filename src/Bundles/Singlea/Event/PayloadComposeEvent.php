<?php declare(strict_types=1);
// SPDX-License-Identifier: BSD-3-Clause

namespace SingleA\Bundles\Singlea\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Allows changing composed user token payload.
 */
final class PayloadComposeEvent extends Event
{
    public function __construct(
        private array $payload,
    ) {}

    public function getPayload(): array
    {
        return $this->payload;
    }

    public function setPayload(array $payload): self
    {
        $this->payload = $payload;

        return $this;
    }
}
