<?php declare(strict_types=1);
// SPDX-License-Identifier: BSD-3-Clause

namespace SingleA\Bundles\Singlea\Tests\Service\UserAttributes;

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

interface TestMixedCacheInterface extends CacheItemPoolInterface, TagAwareCacheInterface
{
}
