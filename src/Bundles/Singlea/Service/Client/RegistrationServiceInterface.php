<?php
// SPDX-License-Identifier: BSD-3-Clause

declare(strict_types=1);

namespace SingleA\Bundles\Singlea\Service\Client;

interface RegistrationServiceInterface
{
    public function register(array $input): RegistrationResult;
}
