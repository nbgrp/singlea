<?php declare(strict_types=1);
// SPDX-License-Identifier: BSD-3-Clause

namespace SingleA\Bundles\Singlea\Tests\Service\UserAttributes;

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use SingleA\Bundles\Singlea\Service\UserAttributes\UserAttributesItem;
use SingleA\Bundles\Singlea\Service\UserAttributes\UserAttributesManager;
use SingleA\Bundles\Singlea\Service\UserAttributes\UserAttributesMarshallerInterface;
use Symfony\Component\Cache\CacheItem;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * @covers \SingleA\Bundles\Singlea\Service\UserAttributes\UserAttributesItem
 * @covers \SingleA\Bundles\Singlea\Service\UserAttributes\UserAttributesManager
 *
 * @internal
 */
final class UserAttributesManagerTest extends TestCase
{
    public function testPersistWithoutTicket(): void
    {
        $pool = $this->createMock(TestMixedCacheInterface::class);
        $pool
            ->expects(self::once())
            ->method('delete')
            ->with(self::matchesRegularExpression('/[[:xdigit:]]{40}/'))
        ;

        $manager = new UserAttributesManager(
            ['test' => $pool],
            $this->createStub(UserAttributesMarshallerInterface::class),
        );

        $ticket = $manager->persist('test', 'tester', ['foo' => 'bar']);

        self::assertMatchesRegularExpression('/.{48}/', bin2hex($ticket));
    }

    public function testPersistWithTicket(): void
    {
        $pool = $this->createMock(TestMixedCacheInterface::class);
        $pool
            ->expects(self::once())
            ->method('delete')
            ->with(self::matchesRegularExpression('/[[:xdigit:]]{40}/'))
        ;

        $manager = new UserAttributesManager(
            ['test' => $pool],
            $this->createStub(UserAttributesMarshallerInterface::class),
        );

        $ticket = $manager->persist('test', 'tester', ['foo' => 'bar'], 'old-ticket');

        self::assertSame('old-ticket', $ticket);
    }

    public function testExists(): void
    {
        $pool = $this->createMock(TestMixedCacheInterface::class);
        $pool
            ->expects(self::once())
            ->method('getItem')
            ->willReturn(new CacheItem())
        ;

        $manager = new UserAttributesManager(
            ['test' => $pool],
            $this->createStub(UserAttributesMarshallerInterface::class),
        );

        self::assertFalse($manager->exists('test', 'foo'));
    }

    public function testSuccessfulProlong(): void
    {
        $pool = $this->createMock(TestMixedCacheInterface::class);
        $pool
            ->expects(self::once())
            ->method('delete')
            ->with('6e03ac0abd06abd159879b71d2fce9506fbaf0089f770591f7e78d6a387a2b8d')
            ->willReturn(true)
        ;
        $pool
            ->expects(self::once())
            ->method('save')
            ->willReturn(true)
        ;

        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects(self::never())
            ->method('warning')
        ;

        $manager = new UserAttributesManager(
            ['test' => $pool],
            $this->createStub(UserAttributesMarshallerInterface::class),
            $logger,
        );

        $manager->prolong('test', 'foo');
    }

    public function testFailedProlong(): void
    {
        $pool = $this->createMock(TestMixedCacheInterface::class);
        $pool
            ->expects(self::once())
            ->method('delete')
            ->with('6e03ac0abd06abd159879b71d2fce9506fbaf0089f770591f7e78d6a387a2b8d')
            ->willReturn(false)
        ;
        $pool
            ->expects(self::never())
            ->method('save')
            ->willReturn(true)
        ;

        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects(self::once())
            ->method('warning')
        ;

        $manager = new UserAttributesManager(
            ['test' => $pool],
            $this->createStub(UserAttributesMarshallerInterface::class),
            $logger,
        );

        $manager->prolong('test', 'foo');
    }

    public function testSuccessfulFind(): void
    {
        $cacheItem = (\Closure::bind(
            static function () {
                $item = new CacheItem();
                $item->value = 'bar';
                $item->isHit = true;
                $item->metadata[ItemInterface::METADATA_TAGS]['tester'] = 'tester';
                $item->metadata[ItemInterface::METADATA_EXPIRY] = 3600;

                return $item;
            },
            null,
            CacheItem::class,
        ))();

        $pool = $this->createMock(TestMixedCacheInterface::class);
        $pool
            ->expects(self::once())
            ->method('getItem')
            ->willReturn($cacheItem)
        ;

        $marshaller = $this->createMock(UserAttributesMarshallerInterface::class);
        $marshaller
            ->expects(self::once())
            ->method('unmarshall')
            ->with('bar', 'foo')
            ->willReturn(['foo' => 'bar'])
        ;

        $manager = new UserAttributesManager(
            ['test' => $pool],
            $marshaller,
        );

        $userAttributesItem = $manager->find('test', 'foo');

        self::assertInstanceOf(UserAttributesItem::class, $userAttributesItem);
        self::assertSame('tester', $userAttributesItem->getIdentifier());
        self::assertSame(['foo' => 'bar'], $userAttributesItem->getAttributes());
        self::assertSame(3600, $userAttributesItem->getTtl());
    }

    public function testMissedFind(): void
    {
        $pool = $this->createMock(TestMixedCacheInterface::class);
        $pool
            ->expects(self::once())
            ->method('getItem')
            ->willReturn(new CacheItem())
        ;

        $manager = new UserAttributesManager(
            ['test' => $pool],
            $this->createStub(UserAttributesMarshallerInterface::class),
        );

        self::assertNull($manager->find('test', 'foo'));
    }

    /**
     * @dataProvider removeProvider
     */
    public function testRemove(bool $delete, bool $expected): void
    {
        $pool = $this->createMock(TestMixedCacheInterface::class);
        $pool
            ->expects(self::once())
            ->method('delete')
            ->with('6e03ac0abd06abd159879b71d2fce9506fbaf0089f770591f7e78d6a387a2b8d')
            ->willReturn($delete)
        ;

        $manager = new UserAttributesManager(
            ['test' => $pool],
            $this->createStub(UserAttributesMarshallerInterface::class),
        );

        self::assertSame($expected, $manager->remove('test', 'foo'));
    }

    public function removeProvider(): \Generator
    {
        yield 'True' => [
            'delete' => true,
            'expected' => true,
        ];

        yield 'False' => [
            'delete' => false,
            'expected' => false,
        ];
    }

    public function testSuccessfulRemoveByUser(): void
    {
        $firstPool = $this->createMock(TestMixedCacheInterface::class);
        $firstPool
            ->expects(self::once())
            ->method('invalidateTags')
            ->with(['tester'])
            ->willReturn(true)
        ;

        $secondPool = $this->createMock(TestMixedCacheInterface::class);
        $secondPool
            ->expects(self::once())
            ->method('invalidateTags')
            ->with(['tester'])
            ->willReturn(true)
        ;

        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects(self::never())
            ->method('warning')
        ;

        $manager = new UserAttributesManager(
            [
                'first' => $firstPool,
                'second' => $secondPool,
            ],
            $this->createStub(UserAttributesMarshallerInterface::class),
            $logger,
        );

        self::assertTrue($manager->removeByUser('tester'));
    }

    public function testFailedRemoveByUser(): void
    {
        $firstPool = $this->createMock(TestMixedCacheInterface::class);
        $firstPool
            ->expects(self::once())
            ->method('invalidateTags')
            ->with(['tester'])
            ->willReturn(true)
        ;

        $secondPool = $this->createMock(TestMixedCacheInterface::class);
        $secondPool
            ->expects(self::once())
            ->method('invalidateTags')
            ->with(['tester'])
            ->willReturn(false)
        ;

        $thirdPool = $this->createMock(TestMixedCacheInterface::class);
        $thirdPool
            ->expects(self::once())
            ->method('invalidateTags')
            ->with(['tester'])
            ->willReturn(false)
        ;

        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects(self::once())
            ->method('warning')
            ->with('Cannot remove user attributes from cache pools: second, third')
        ;

        $manager = new UserAttributesManager(
            [
                'first' => $firstPool,
                'second' => $secondPool,
                'third' => $thirdPool,
            ],
            $this->createStub(UserAttributesMarshallerInterface::class),
            $logger,
        );

        self::assertFalse($manager->removeByUser('tester'));
    }

    public function testGetInvalidPool(): void
    {
        $manager = new UserAttributesManager(
            ['test' => $this->createStub(TestMixedCacheInterface::class)],
            $this->createStub(UserAttributesMarshallerInterface::class),
        );

        $this->expectException(\OutOfRangeException::class);
        $this->expectExceptionMessage('There is no cache pool in realm "unknown".');

        $manager->getPool('unknown');
    }
}
