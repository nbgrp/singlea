<?php
// SPDX-License-Identifier: BSD-3-Clause

declare(strict_types=1);

namespace SingleA\Bundles\Singlea\Service\UserAttributes;

interface UserAttributesItemInterface
{
    public function getIdentifier(): string;

    public function getAttributes(): array;

    public function getTtl(): ?int;
}
