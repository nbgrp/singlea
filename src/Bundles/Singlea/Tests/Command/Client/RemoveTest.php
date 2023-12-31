<?php
// SPDX-License-Identifier: BSD-3-Clause

declare(strict_types=1);

namespace SingleA\Bundles\Singlea\Tests\Command\Client;

use PHPUnit\Framework\TestCase;
use SingleA\Bundles\Singlea\Command\Client\Remove;
use SingleA\Contracts\Persistence\ClientManagerInterface;
use SingleA\Contracts\Persistence\FeatureConfigManagerInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @covers \SingleA\Bundles\Singlea\Command\Client\Remove
 *
 * @internal
 */
final class RemoveTest extends TestCase
{
    private const UUID = '1ec7e40b-c569-6eb0-8d6c-d98bed082582';
    private const BASE58 = '4oTPRz6Ntkn5hcaYkCjhLu';

    public function testSuccessfulSilenceRemoved(): void
    {
        $configManager = $this->createMock(FeatureConfigManagerInterface::class);
        $configManager
            ->expects(self::once())
            ->method('remove')
            ->with(self::UUID)
        ;

        $clientManager = $this->createMock(ClientManagerInterface::class);
        $clientManager
            ->expects(self::once())
            ->method('remove')
            ->with(self::UUID)
            ->willReturn(1)
        ;
        $clientManager
            ->expects(self::once())
            ->method('getLastAccess')
            ->with(self::UUID)
            ->willReturn(new \DateTimeImmutable())
        ;

        $tester = self::getCommandTester([$configManager], $clientManager);

        $tester->execute([
            'client-id' => self::BASE58,
            '--yes' => true,
        ]);

        $tester->assertCommandIsSuccessful();
        self::assertStringContainsString('Client was removed.', $tester->getDisplay());
    }

    public function testSuccessfulNothingRemoved(): void
    {
        $configManager = $this->createMock(FeatureConfigManagerInterface::class);
        $configManager
            ->expects(self::never())
            ->method('remove')
        ;

        $clientManager = $this->createMock(ClientManagerInterface::class);
        $clientManager
            ->expects(self::once())
            ->method('remove')
            ->with(self::UUID)
            ->willReturn(0)
        ;
        $clientManager
            ->expects(self::once())
            ->method('getLastAccess')
            ->with(self::UUID)
            ->willReturn(new \DateTimeImmutable())
        ;

        $tester = self::getCommandTester([$configManager], $clientManager);

        $tester->setInputs(['y']);
        $tester->execute(['client-id' => self::BASE58]);

        $tester->assertCommandIsSuccessful();
        self::assertStringContainsString('Nothing were removed.', $tester->getDisplay());
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
            ->expects(self::never())
            ->method('remove')
        ;
        $clientManager
            ->expects(self::once())
            ->method('getLastAccess')
            ->with(self::UUID)
            ->willReturn(new \DateTimeImmutable())
        ;

        $tester = self::getCommandTester([$configManager], $clientManager);

        $tester->setInputs([self::BASE58, 'n']);
        $tester->execute([]);

        $tester->assertCommandIsSuccessful();
        self::assertStringContainsString('Nothing were removed.', $tester->getDisplay());
    }

    public function testInvalidUuid(): void
    {
        $configManager = $this->createMock(FeatureConfigManagerInterface::class);
        $configManager
            ->expects(self::never())
            ->method('remove')
        ;

        $clientManager = $this->createMock(ClientManagerInterface::class);
        $clientManager
            ->expects(self::never())
            ->method('remove')
        ;

        $tester = self::getCommandTester([$configManager], $clientManager);

        $tester->execute(['client-id' => 'invalid']);

        self::assertStringContainsString('Invalid client ID.', $tester->getDisplay());
        self::assertSame(Command::FAILURE, $tester->getStatusCode());
    }

    private static function getCommandTester(iterable $configManagers, ClientManagerInterface $clientManager): CommandTester
    {
        $application = new Application();
        $application->add(new Remove($configManagers, $clientManager));

        $command = $application->find('client:remove');

        return new CommandTester($command);
    }
}
