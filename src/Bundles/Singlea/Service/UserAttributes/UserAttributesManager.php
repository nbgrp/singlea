<?php
// SPDX-License-Identifier: BSD-3-Clause

declare(strict_types=1);

namespace SingleA\Bundles\Singlea\Service\UserAttributes;

use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

final class UserAttributesManager implements UserAttributesManagerInterface
{
    /**
     * @param array<string, CacheItemPoolInterface&TagAwareCacheInterface> $pools
     */
    public function __construct(
        private readonly array $pools,
        private readonly UserAttributesMarshallerInterface $marshaller,
        private readonly ?LoggerInterface $logger = null,
    ) {}

    public function exists(string $realm, string $ticket): bool
    {
        $pool = $this->getPool($realm);
        $key = self::getKey($realm, $ticket);

        return $pool->getItem($key)->isHit();
    }

    public function persist(string $realm, string $userIdentifier, array $attributes, ?string $ticket = null): string
    {
        if ($ticket === null) {
            $ticket = random_bytes(\SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        }

        $pool = $this->getPool($realm);
        $key = self::getKey($realm, $ticket);

        $pool->delete($key);
        /** @psalm-suppress ArgumentTypeCoercion */
        $pool->get($key, function (ItemInterface $item) use ($userIdentifier, $attributes, $ticket): string {
            $item->tag($userIdentifier);

            return $this->marshaller->marshall($attributes, $ticket);
        }, 0);

        return $ticket;
    }

    public function prolong(string $realm, string $ticket): bool
    {
        $pool = $this->getPool($realm);
        $key = self::getKey($realm, $ticket);

        $item = $pool->getItem($key);

        if (!$pool->delete($key)) {
            $this->logger?->warning('Cannot delete key "'.$key.'" during user attributes prolongation.');

            return false;
        }

        return $pool->save($item);
    }

    /**
     * @psalm-suppress MixedArgument
     */
    public function find(string $realm, string $ticket): ?UserAttributesItem
    {
        $pool = $this->getPool($realm);
        $key = self::getKey($realm, $ticket);

        $item = $pool->getItem($key);
        \assert($item instanceof ItemInterface);
        if (!$item->isHit()) {
            return null;
        }

        $metadata = $item->getMetadata();

        /** @psalm-suppress InvalidCast */
        return new UserAttributesItem(
            (string) (array_values($metadata[ItemInterface::METADATA_TAGS] ?? [])[0] ?? throw new \RuntimeException('Unknown user (user cache has no tag with user identifier).')),
            $this->marshaller->unmarshall((string) $item->get(), $ticket), // @phpstan-ignore-line
            $metadata[ItemInterface::METADATA_EXPIRY] ?? null,
        );
    }

    public function remove(string $realm, string $ticket): bool
    {
        $pool = $this->getPool($realm);
        $key = self::getKey($realm, $ticket);

        return $pool->delete($key);
    }

    public function removeByUser(string $userIdentifier): bool
    {
        $failed = array_keys(array_filter($this->pools, static fn (TagAwareCacheInterface $pool): bool => !$pool->invalidateTags([$userIdentifier])));
        if ($failed) {
            $this->logger?->warning('Cannot remove user attributes from cache pools: '.implode(', ', $failed));

            return false;
        }

        return true;
    }

    /**
     * @return CacheItemPoolInterface&TagAwareCacheInterface
     */
    public function getPool(string $realm)
    {
        return $this->pools[$realm] ?? throw new \OutOfRangeException('There is no cache pool in realm "'.$realm.'".');
    }

    private static function getKey(string $realm, string $ticket): string
    {
        return hash('sha256', $realm.$ticket);
    }
}
