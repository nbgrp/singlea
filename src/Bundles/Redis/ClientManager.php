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
        private readonly \Predis\ClientInterface|\Redis|\RedisCluster|\Relay\Relay $redis,
        private readonly ?LoggerInterface $logger = null,
    ) {}

    public function exists(string $id, bool $touch = true): bool
    {
        $exists = (bool) $this->redis->hExists($this->key, $id);
        if ($exists && $touch) {
            $this->touch($id);
        }

        return $exists;
    }

    public function touch(string $id): void
    {
        try {
            $this->redis->hSet($this->key, $id, (string) time());
        } finally {
            $this->logger?->debug('Key "'.$this->key.'": client '.$id.' touched.');
        }
    }

    public function getLastAccess(string $id): \DateTimeImmutable
    {
        $timestamp = $this->redis->hGet($this->key, $id);
        if (!is_numeric($timestamp)) {
            throw new \InvalidArgumentException('Unknown id specified: '.$id);
        }

        return new \DateTimeImmutable("@{$timestamp}");
    }

    public function findInactiveSince(\DateTimeInterface $datetime): iterable
    {
        $inactiveIds = null;
        $error = null;

        $script = <<<'LUA'
            local last_access = redis.call('hgetall', KEYS[1])
            local ids = {}

            for i = 1, #last_access, 2 do
               if last_access[i + 1] <= KEYS[2] then
                  ids[#ids + 1] = last_access[i]
               end
            end

            return ids
            LUA;
        $args = [$this->key, (string) $datetime->getTimestamp()];

        try {
            /** @psalm-suppress InvalidArgument, MixedAssignment */
            $inactiveIds = $this->redis instanceof \Predis\ClientInterface
                ? $this->redis->eval($script, \count($args), ...$args)
                : $this->redis->eval($script, $args, \count($args));
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
        $ids = $this->redis->hKeys($this->key);

        if (!\is_array($ids) || empty($ids)) {
            return null;
        }

        sort($ids);

        return (string) reset($ids);
    }

    /**
     * @psalm-suppress InvalidArgument, MixedAssignment
     */
    public function remove(string ...$ids): int
    {
        if (empty($ids)) {
            return 0;
        }

        try {
            if ($this->redis instanceof \Predis\ClientInterface) {
                /** @psalm-suppress RedundantCast */
                return (int) $this->redis->hDel($this->key, $ids);
            }

            $result = $this->redis->hDel($this->key, ...$ids);

            return \is_int($result) ? $result : 0;
        } finally {
            $this->logger?->debug('Key "'.$this->key.'": clients removed: '.implode(', ', $ids).'.');
        }
    }

    private function getRedisLastError(): ?string
    {
        $error = null;
        if ($this->redis instanceof \Redis || $this->redis instanceof \RedisCluster || $this->redis instanceof \Relay\Relay) {
            /** @var string|null $error */
            $error = $this->redis->getLastError();
            $this->redis->clearLastError();
        }

        return $error;
    }
}
