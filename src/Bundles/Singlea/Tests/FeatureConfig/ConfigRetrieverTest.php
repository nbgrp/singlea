<?php declare(strict_types=1);
// SPDX-License-Identifier: BSD-3-Clause

namespace SingleA\Bundles\Singlea\Tests\FeatureConfig;

use PHPUnit\Framework\TestCase;
use SingleA\Bundles\Singlea\FeatureConfig\ConfigRetriever;
use SingleA\Bundles\Singlea\Tests\TestConfigInterface;
use SingleA\Contracts\Persistence\FeatureConfigManagerInterface;

/**
 * @covers \SingleA\Bundles\Singlea\FeatureConfig\ConfigRetriever
 *
 * @internal
 */
final class ConfigRetrieverTest extends TestCase
{
    private ConfigRetriever $configRetriever;

    public function testExists(): void
    {
        $anotherConfigManager = $this->createMock(FeatureConfigManagerInterface::class);
        $anotherConfigManager
            ->expects(self::exactly(2))
            ->method('supports')
            ->willReturn(false)
        ;
        $anotherConfigManager
            ->expects(self::never())
            ->method('exists')
        ;

        $testConfigManager = $this->createMock(FeatureConfigManagerInterface::class);
        $testConfigManager
            ->expects(self::exactly(2))
            ->method('supports')
            ->willReturnCallback(static fn (string $configInterface): bool => $configInterface === TestConfigInterface::class)
        ;
        $testConfigManager
            ->expects(self::once())
            ->method('exists')
            ->with('client')
            ->willReturn(true)
        ;

        $this->configRetriever = new ConfigRetriever([
            $anotherConfigManager,
            $testConfigManager,
        ]);

        self::assertTrue($this->configRetriever->exists(TestConfigInterface::class, 'client'));
        self::assertFalse($this->configRetriever->exists(\stdClass::class, 'client'));
    }

    public function testFind(): void
    {
        $anotherConfigManager = $this->createMock(FeatureConfigManagerInterface::class);
        $anotherConfigManager
            ->expects(self::exactly(2))
            ->method('supports')
            ->willReturn(false)
        ;
        $anotherConfigManager
            ->expects(self::never())
            ->method('find')
        ;

        $config = $this->createMock(TestConfigInterface::class);

        $testConfigManager = $this->createMock(FeatureConfigManagerInterface::class);
        $testConfigManager
            ->expects(self::exactly(2))
            ->method('supports')
            ->willReturnCallback(static fn (string $configInterface): bool => $configInterface === TestConfigInterface::class)
        ;
        $testConfigManager
            ->expects(self::once())
            ->method('find')
            ->with('client', 'secret')
            ->willReturn($config)
        ;

        $this->configRetriever = new ConfigRetriever([
            $anotherConfigManager,
            $testConfigManager,
        ]);

        self::assertSame($config, $this->configRetriever->find(TestConfigInterface::class, 'client', 'secret'));
        self::assertNull($this->configRetriever->find(\stdClass::class, 'client', 'secret'));
    }
}
