<?php declare(strict_types=1);
// SPDX-License-Identifier: BSD-3-Clause

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
     * @dataProvider existsProvider
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
            ->method('hExists')
            ->with($key, $id)
            ->willReturn($exists)
        ;

        $logger = $this->createMock(LoggerInterface::class);

        if ($touch) {
            $redis
                ->expects(self::once())
                ->method('hSet')
                ->with($key, $id, self::stringContains(substr((string) time(), 0, -2)))
            ;

            $logger
                ->expects(self::once())
                ->method('debug')
            ;
        } else {
            $redis
                ->expects(self::never())
                ->method('hSet')
            ;

            $logger
                ->expects(self::never())
                ->method('debug')
            ;
        }

        $clientManager = new ClientManager($key, $redis, $logger);

        self::assertSame($expected, $clientManager->exists($id, $touch));
    }

    public function existsProvider(): \Generator
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
            ->method('hGet')
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
            ->method('hGet')
            ->with($key, $id)
            ->willReturn('1609495200')
        ;

        $clientManager = new ClientManager($key, $redis, null);

        self::assertSame('01.01.2021 10:00:00', $clientManager->getLastAccess($id)->format('d.m.Y H:i:s'));
    }

    /**
     * @dataProvider findInactiveSinceProvider
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

    public function findInactiveSinceProvider(): \Generator
    {
        yield 'Non-empty result' => [
            'evalResult' => ['1', '2'],
            'expected' => ['1', '2'],
        ];

        yield 'Empty result' => [
            'evalResult' => null,
            'expected' => [],
        ];
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
     * @dataProvider removeProvider
     */
    public function testRemove(\Redis $redis, LoggerInterface $logger, array $ids, int $expected): void
    {
        $clientManager = new ClientManager('key', $redis, $logger);

        self::assertSame($expected, $clientManager->remove(...$ids));
    }

    public function removeProvider(): \Generator
    {
        yield 'Removed 2 items' => [
            'redis' => (function (): \Redis {
                $redis = $this->createMock(\Redis::class);
                $redis
                    ->expects(self::once())
                    ->method('hDel')
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
                    ->method('hDel')
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
                    ->method('hDel')
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
