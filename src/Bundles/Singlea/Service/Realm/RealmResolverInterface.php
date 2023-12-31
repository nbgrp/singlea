<?php
// SPDX-License-Identifier: BSD-3-Clause

declare(strict_types=1);

namespace SingleA\Bundles\Singlea\Service\Realm;

use Symfony\Component\HttpFoundation\Request;

interface RealmResolverInterface
{
    public function resolve(Request $request): string;
}
