<?php
// SPDX-License-Identifier: BSD-3-Clause

declare(strict_types=1);

namespace SingleA\Bundles\Singlea\Service\Signature;

use SingleA\Bundles\Singlea\FeatureConfig\Signature\SignatureConfigInterface;
use Symfony\Component\HttpFoundation\Request;

interface SignatureServiceInterface
{
    public function check(Request $request, SignatureConfigInterface $config): void;
}
