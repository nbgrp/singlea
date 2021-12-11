<?php declare(strict_types=1);
// SPDX-License-Identifier: BSD-3-Clause

namespace SingleA\Bundles\Singlea\Service\UserAttributes;

interface UserAttributesItemInterface
{
    public function getIdentifier(): string;

    public function getAttributes(): array;

    public function getTtl(): ?int;
}
