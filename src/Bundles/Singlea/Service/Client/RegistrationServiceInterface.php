<?php declare(strict_types=1);
// SPDX-License-Identifier: BSD-3-Clause

namespace SingleA\Bundles\Singlea\Service\Client;

interface RegistrationServiceInterface
{
    public function register(array $input): RegistrationResult;
}
