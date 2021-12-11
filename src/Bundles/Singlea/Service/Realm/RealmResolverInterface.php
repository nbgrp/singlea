<?php declare(strict_types=1);
// SPDX-License-Identifier: BSD-3-Clause

namespace SingleA\Bundles\Singlea\Service\Realm;

use Symfony\Component\HttpFoundation\Request;

interface RealmResolverInterface
{
    public function resolve(Request $request): string;
}
