<?php
// SPDX-License-Identifier: BSD-3-Clause

declare(strict_types=1);

namespace SingleA\Bundles\Redis;

use Psr\Log\LoggerInterface;
use SingleA\Contracts\Persistence\ClientManagerInterface;

final class ClientManager implements ClientManagerInterface
{
    public function __construct(
        private readonly string $key,
        private readonly \Predis\ClientInterface|\Redis|\RedisCluster $redis,
        private readonly ?LoggerInterface $logger = null,
    ) {}

    public function exists(string $id, bool $touch = true): bool
    {
        $exists = (bool) $this->redis->hexists($this->key, $id);
        if ($exists && $touch) {
            $this->touch($id);
        }

        return $exists;
    }

    public function touch(string $id): void
    {
        try {
            $this->redis->hset($this->key, $id, (string) time());
        } finally {
            $this->logger?->debug('Key "'.$this->key.'": client '.$id.' touched.');
        }
    }

    public function getLastAccess(string $id): \DateTimeImmutable
    {
        $timestamp = $this->redis->hget($this->key, $id);
        if (!is_numeric($timestamp)) {
            throw new \InvalidArgumentException('Unknown id specified: '.$id);
        }

        return new \DateTimeImmutable("@{$timestamp}");
    }

    public function findInactiveSince(\DateTimeInterface $datetime): iterable
    {
        $inactiveIds = null;
        $error = null;

        try {
            /** @psalm-suppress MixedAssignment */
            $inactiveIds = $this->redis->eval(
                <<<'LUA'
                    local last_access = redis.call('hgetall', KEYS[1])
                    local ids = {}

                    for i = 1, #last_access, 2 do
                       if last_access[i + 1] <= KEYS[2] then
                          ids[#ids + 1] = last_access[i]
                       end
                    end

                    return ids
                    LUA,
                [
                    $this->key,
                    $datetime->getTimestamp(),
                ],
                2,
            );
        } catch (\Throwable $exception) {
            $error = $exception->getMessage();
        }

        if (\is_array($inactiveIds)) {
            /** @psalm-suppress MixedArgument */
            return array_map('strval', $inactiveIds);
        }

        $error ??= $this->getRedisLastError();

        if (\is_string($error)) {
            $this->logger?->error('Error during seek of inactive clients ids: '.$error);
        }

        return [];
    }

    public function findOldest(): ?string
    {
        $ids = $this->redis->hkeys($this->key);

        if (!\is_array($ids) || empty($ids)) {
            return null;
        }

        sort($ids);

        return (string) reset($ids);
    }

    public function remove(string ...$ids): int
    {
        if (empty($ids)) {
            return 0;
        }

        try {
            /** @psalm-suppress InvalidArgument, InvalidCast */
            return (int) $this->redis->hdel($this->key, ...$ids); // @phan-suppress-current-line PhanParamTooManyUnpack, PhanTypeMismatchArgumentProbablyReal
        } finally {
            $this->logger?->debug('Key "'.$this->key.'": clients removed: '.implode(', ', $ids).'.');
        }
    }

    private function getRedisLastError(): ?string
    {
        $error = null;
        if ($this->redis instanceof \Redis || $this->redis instanceof \RedisCluster) {
            $error = $this->redis->getLastError();
            $this->redis->clearLastError();
        }

        return $error;
    }
}
