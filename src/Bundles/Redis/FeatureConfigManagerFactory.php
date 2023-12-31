<?php
// SPDX-License-Identifier: BSD-3-Clause

declare(strict_types=1);

namespace SingleA\Bundles\Redis;

use Psr\Log\LoggerInterface;
use SingleA\Contracts\FeatureConfig\FeatureConfigInterface;
use SingleA\Contracts\Marshaller\FeatureConfigEncryptorInterface;
use SingleA\Contracts\Marshaller\FeatureConfigMarshallerInterface;
use SingleA\Contracts\Persistence\FeatureConfigManagerInterface;

final class FeatureConfigManagerFactory
{
    public function __invoke(
        string $key,
        \Predis\ClientInterface|\Redis|\RedisCluster $redis,
        FeatureConfigMarshallerInterface $marshaller,
        FeatureConfigEncryptorInterface $encryptor,
        bool $required,
        ?LoggerInterface $logger = null,
    ): FeatureConfigManagerInterface {
        return new class($key, $redis, $marshaller, $encryptor, $required, $logger) implements FeatureConfigManagerInterface {
            public function __construct(
                private readonly string $key,
                private readonly \Predis\ClientInterface|\Redis|\RedisCluster $redis,
                private readonly FeatureConfigMarshallerInterface $marshaller,
                private readonly FeatureConfigEncryptorInterface $encryptor,
                private readonly bool $required,
                private readonly ?LoggerInterface $logger,
            ) {}

            public function supports(FeatureConfigInterface|string $config): bool
            {
                return $this->marshaller->supports($config);
            }

            public function isRequired(): bool
            {
                return $this->required;
            }

            public function exists(string $id): bool
            {
                return (bool) $this->redis->hexists($this->key, $id);
            }

            public function persist(string $id, FeatureConfigInterface $config, string $secret): void
            {
                $value = $this->marshaller->marshall($config);
                $value = $this->encryptor->encrypt($value, $secret);

                try {
                    $this->redis->hset($this->key, $id, $value);
                } finally {
                    $this->logger?->debug('Key "'.$this->key.'": config '.$id.' persisted.');
                }
            }

            public function find(string $id, string $secret): ?FeatureConfigInterface
            {
                /** @var string|false $value */
                $value = $this->redis->hget($this->key, $id);
                if (\is_string($value)) {
                    return $this->marshaller->unmarshall(
                        $this->encryptor->decrypt($value, $secret),
                    );
                }

                return null;
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
                    $this->logger?->debug('Key "'.$this->key.'": configs removed: '.implode(', ', $ids).'.');
                }
            }
        };
    }
}
