<?php
// SPDX-License-Identifier: BSD-3-Clause

declare(strict_types=1);

namespace SingleA\Bundles\Singlea\Tests\Command\Client;

use PHPUnit\Framework\TestCase;
use SingleA\Bundles\Singlea\Command\Client\Purge;
use SingleA\Contracts\Persistence\ClientManagerInterface;
use SingleA\Contracts\Persistence\FeatureConfigManagerInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @covers \SingleA\Bundles\Singlea\Command\Client\Purge
 *
 * @internal
 */
final class PurgeTest extends TestCase
{
    public function testSuccessfulPurge(): void
    {
        $configManager = $this->createMock(FeatureConfigManagerInterface::class);
        $configManager
            ->expects(self::once())
            ->method('remove')
            ->with(
                '1ec7d443-a7bf-6112-89fe-3923cde81694',
                '1ec7e256-14e9-6646-a3fa-37d3996cc17f',
            )
        ;

        $clientManager = $this->createMock(ClientManagerInterface::class);
        $clientManager
            ->expects(self::once())
            ->method('findInactiveSince')
            ->willReturn([
                '1ec7d443-a7bf-6112-89fe-3923cde81694',
                '1ec7e256-14e9-6646-a3fa-37d3996cc17f',
            ])
        ;
        $clientManager
            ->expects($matcher = self::exactly(2))
            ->method('getLastAccess')
            ->willReturnCallback(static function (string $id) use ($matcher): \DateTimeImmutable {
                switch ($matcher->getInvocationCount()) {
                    case 1:
                        self::assertSame('1ec7d443-a7bf-6112-89fe-3923cde81694', $id);
                        break;

                    case 2:
                        self::assertSame('1ec7e256-14e9-6646-a3fa-37d3996cc17f', $id);
                        break;

                    default:
                        throw new \RuntimeException('Unexpected');
                }

                return new \DateTimeImmutable();
            })
        ;
        $clientManager
            ->expects(self::once())
            ->method('remove')
            ->with(
                '1ec7d443-a7bf-6112-89fe-3923cde81694',
                '1ec7e256-14e9-6646-a3fa-37d3996cc17f',
            )
            ->willReturn(2)
        ;

        $tester = self::getCommandTester([$configManager], $clientManager);

        $tester->setInputs(['y']);
        $tester->execute(['days' => '10']);

        $tester->assertCommandIsSuccessful();
        self::assertStringContainsString('2 clients were removed.', $tester->getDisplay());
    }

    public function testSuccessfulSilencePurge(): void
    {
        $configManager = $this->createMock(FeatureConfigManagerInterface::class);
        $configManager
            ->expects(self::once())
            ->method('remove')
            ->with('1ec7e256-14e9-6646-a3fa-37d3996cc17f')
        ;

        $clientManager = $this->createMock(ClientManagerInterface::class);
        $clientManager
            ->expects(self::once())
            ->method('findInactiveSince')
            ->willReturn(['1ec7e256-14e9-6646-a3fa-37d3996cc17f'])
        ;
        $clientManager
            ->expects(self::once())
            ->method('getLastAccess')
            ->with('1ec7e256-14e9-6646-a3fa-37d3996cc17f')
            ->willReturn(new \DateTimeImmutable())
        ;
        $clientManager
            ->expects(self::once())
            ->method('remove')
            ->with('1ec7e256-14e9-6646-a3fa-37d3996cc17f')
            ->willReturn(1)
        ;

        $tester = self::getCommandTester([$configManager], $clientManager);

        $tester->execute([
            'days' => '10',
            '--yes' => true,
        ]);

        $tester->assertCommandIsSuccessful();
        self::assertStringContainsString('1 client was removed.', $tester->getDisplay());
    }

    public function testSuccessfulNothingPurge(): void
    {
        $configManager = $this->createMock(FeatureConfigManagerInterface::class);
        $configManager
            ->expects(self::never())
            ->method('remove')
        ;

        $clientManager = $this->createMock(ClientManagerInterface::class);
        $clientManager
            ->expects(self::once())
            ->method('findInactiveSince')
            ->willReturn([])
        ;
        $clientManager
            ->expects(self::never())
            ->method('remove')
        ;

        $tester = self::getCommandTester([$configManager], $clientManager);

        $tester->execute(['days' => '100']);

        $tester->assertCommandIsSuccessful();
        self::assertStringContainsString('There is no inactive clients since', $tester->getDisplay());
    }

    public function testSuccessfulInteractiveRefuse(): void
    {
        $configManager = $this->createMock(FeatureConfigManagerInterface::class);
        $configManager
            ->expects(self::never())
            ->method('remove')
        ;

        $clientManager = $this->createMock(ClientManagerInterface::class);
        $clientManager
            ->expects(self::once())
            ->method('findInactiveSince')
            ->willReturn(['1ec7e256-14e9-6646-a3fa-37d3996cc17f'])
        ;
        $clientManager
            ->expects(self::never())
            ->method('remove')
        ;
        $clientManager
            ->expects(self::once())
            ->method('getLastAccess')
            ->with('1ec7e256-14e9-6646-a3fa-37d3996cc17f')
            ->willReturn(new \DateTimeImmutable())
        ;

        $tester = self::getCommandTester([$configManager], $clientManager);

        $tester->setInputs(['100', 'n']);
        $tester->execute([]);

        $tester->assertCommandIsSuccessful();
        self::assertStringContainsString('Nothing were removed.', $tester->getDisplay());
    }

    public function testInvalidDays(): void
    {
        $clientManager = $this->createMock(ClientManagerInterface::class);
        $clientManager
            ->expects(self::never())
            ->method('findInactiveSince')
        ;

        $tester = self::getCommandTester([], $clientManager);

        $tester->execute(['days' => '-1']);

        self::assertSame(Command::FAILURE, $tester->getStatusCode());
        self::assertStringContainsString('Maximum allowed inactive period must be specified as positive integer.', $tester->getDisplay());
    }

    private static function getCommandTester(iterable $configManagers, ClientManagerInterface $clientManager): CommandTester
    {
        $application = new Application();
        $application->add(new Purge($configManagers, $clientManager));

        $command = $application->find('client:purge');

        return new CommandTester($command);
    }
}
