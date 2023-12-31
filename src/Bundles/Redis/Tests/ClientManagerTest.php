<?php
// SPDX-License-Identifier: BSD-3-Clause

declare(strict_types=1);

namespace SingleA\Bundles\Redis\Tests;

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use SingleA\Bundles\Redis\ClientManager;

/**
 * @covers \SingleA\Bundles\Redis\ClientManager
 *
 * @internal
 */
final class ClientManagerTest extends TestCase
{
    /**
     * @dataProvider provideExistsCases
     *
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    public function testExists(bool $touch, bool $exists, bool $expected): void
    {
        $key = 'key';
        $id = 'id';

        $redis = $this->createMock(\Redis::class);
        $redis
            ->expects(self::once())
            ->method('hexists')
            ->with($key, $id)
            ->willReturn($exists)
        ;

        $logger = $this->createMock(LoggerInterface::class);

        if ($touch) {
            $redis
                ->expects(self::once())
                ->method('hset')
                ->with($key, $id, self::stringContains(substr((string) time(), 0, -2)))
            ;

            $logger
                ->expects(self::once())
                ->method('debug')
            ;
        } else {
            $redis
                ->expects(self::never())
                ->method('hset')
            ;

            $logger
                ->expects(self::never())
                ->method('debug')
            ;
        }

        $clientManager = new ClientManager($key, $redis, $logger);

        self::assertSame($expected, $clientManager->exists($id, $touch));
    }

    public function provideExistsCases(): iterable
    {
        yield 'Not exists' => [
            'touch' => false,
            'exists' => false,
            'expected' => false,
        ];

        yield 'Exists without touch' => [
            'touch' => false,
            'exists' => true,
            'expected' => true,
        ];

        yield 'Exists with touch' => [
            'touch' => true,
            'exists' => true,
            'expected' => true,
        ];
    }

    public function testGetLastAccessUnknownException(): void
    {
        $key = 'key';
        $id = 'id';

        $redis = $this->createMock(\Redis::class);
        $redis
            ->expects(self::once())
            ->method('hget')
            ->with($key, $id)
            ->willReturn(false)
        ;

        $clientManager = new ClientManager($key, $redis, null);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown id specified: id');

        $clientManager->getLastAccess($id);
    }

    public function testGetLastAccess(): void
    {
        $key = 'key';
        $id = 'id';

        $redis = $this->createMock(\Redis::class);
        $redis
            ->expects(self::once())
            ->method('hget')
            ->with($key, $id)
            ->willReturn('1609495200')
        ;

        $clientManager = new ClientManager($key, $redis, null);

        self::assertSame('01.01.2021 10:00:00', $clientManager->getLastAccess($id)->format('d.m.Y H:i:s'));
    }

    /**
     * @testWith [["1", "2"], ["1", "2"]]
     *           [null, []]
     */
    public function testFindInactiveSince(mixed $evalResult, array $expected): void
    {
        $redis = $this->createMock(\Redis::class);
        $redis
            ->expects(self::once())
            ->method('eval')
            ->willReturn($evalResult)
        ;

        $clientManager = new ClientManager('key', $redis, null);
        $ids = $clientManager->findInactiveSince(new \DateTimeImmutable());

        self::assertSame($expected, [...$ids]);
    }

    public function testFindInactiveSinceLuaError(): void
    {
        $redis = $this->createMock(\Redis::class);
        $redis
            ->expects(self::once())
            ->method('eval')
            ->willReturn(null)
        ;
        $redis
            ->expects(self::once())
            ->method('getLastError')
            ->willReturn('Error text')
        ;
        $redis
            ->expects(self::once())
            ->method('clearLastError')
        ;

        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects(self::once())
            ->method('error')
            ->with(self::stringContains('Error text'))
        ;

        (new ClientManager('key', $redis, $logger))->findInactiveSince(new \DateTimeImmutable());
    }

    /**
     * @testWith [["1ec7e39f-5d3e-6d2a-9768-cdedc4b4ad25", "1ec7d443-a7bf-6112-89fe-3923cde81694"], "1ec7d443-a7bf-6112-89fe-3923cde81694"]
     *           [[], null]
     */
    public function testFindOldest(array $keys, ?string $expected): void
    {
        $redis = $this->createMock(\Redis::class);
        $redis
            ->expects(self::once())
            ->method('hkeys')
            ->willReturn($keys)
        ;

        $clientManager = new ClientManager('key', $redis, null);
        $id = $clientManager->findOldest();

        self::assertSame($expected, $id);
    }

    /**
     * @dataProvider provideRemoveCases
     */
    public function testRemove(\Redis $redis, LoggerInterface $logger, array $ids, int $expected): void
    {
        $clientManager = new ClientManager('key', $redis, $logger);

        self::assertSame($expected, $clientManager->remove(...$ids));
    }

    public function provideRemoveCases(): iterable
    {
        yield 'Removed 2 items' => [
            'redis' => (function (): \Redis {
                $redis = $this->createMock(\Redis::class);
                $redis
                    ->expects(self::once())
                    ->method('hdel')
                    ->with('key', '1', '2')
                    ->willReturn(2)
                ;

                return $redis;
            })(),
            'logger' => (function (): LoggerInterface {
                $logger = $this->createMock(LoggerInterface::class);
                $logger
                    ->expects(self::once())
                    ->method('debug')
                ;

                return $logger;
            })(),
            'ids' => ['1', '2'],
            'expected' => 2,
        ];

        yield 'Removed 0 items' => [
            'redis' => (function (): \Redis {
                $redis = $this->createMock(\Redis::class);
                $redis
                    ->expects(self::once())
                    ->method('hdel')
                    ->with('key', '1', '2')
                    ->willReturn(0)
                ;

                return $redis;
            })(),
            'logger' => (function (): LoggerInterface {
                $logger = $this->createMock(LoggerInterface::class);
                $logger
                    ->expects(self::once())
                    ->method('debug')
                ;

                return $logger;
            })(),
            'ids' => ['1', '2'],
            'expected' => 0,
        ];

        yield 'Removed nothing (false)' => [
            'redis' => (function (): \Redis {
                $redis = $this->createMock(\Redis::class);
                $redis
                    ->expects(self::never())
                    ->method('hdel')
                ;

                return $redis;
            })(),
            'logger' => (function (): LoggerInterface {
                $logger = $this->createMock(LoggerInterface::class);
                $logger
                    ->expects(self::never())
                    ->method('debug')
                ;

                return $logger;
            })(),
            'ids' => [],
            'expected' => 0,
        ];
    }
}
