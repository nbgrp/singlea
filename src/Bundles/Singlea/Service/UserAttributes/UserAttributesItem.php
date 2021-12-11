<?php declare(strict_types=1);
// SPDX-License-Identifier: BSD-3-Clause

namespace SingleA\Bundles\Singlea\Service\UserAttributes;

final class UserAttributesItem implements UserAttributesItemInterface
{
    public function __construct(
        private string $identifier,
        private array $attributes,
        private ?int $ttl,
    ) {}

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function getTtl(): ?int
    {
        return $this->ttl;
    }
}
