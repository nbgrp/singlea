<?php
// SPDX-License-Identifier: BSD-3-Clause

declare(strict_types=1);

namespace SingleA\Bundles\Singlea\Tests\Service\UserAttributes;

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

interface TestMixedCacheInterface extends CacheItemPoolInterface, TagAwareCacheInterface {}
