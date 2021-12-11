<?php declare(strict_types=1);
// SPDX-License-Identifier: BSD-3-Clause

namespace SingleA\Bundles\Singlea\Tests\Command\Client;

use PHPUnit\Framework\TestCase;
use SingleA\Bundles\Singlea\Command\Client\Oldest;
use SingleA\Contracts\Persistence\ClientManagerInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @covers \SingleA\Bundles\Singlea\Command\Client\Oldest
 *
 * @internal
 */
final class OldestTest extends TestCase
{
    private const UUID = '1ec7e40b-c569-6eb0-8d6c-d98bed082582';

    public function testSuccessful(): void
    {
        $clientManager = $this->createMock(ClientManagerInterface::class);
        $clientManager
            ->expects(self::once())
            ->method('findOldest')
            ->willReturn(self::UUID)
        ;
        $clientManager
            ->expects(self::once())
            ->method('getLastAccess')
            ->with(self::UUID)
            ->willReturn(new \DateTimeImmutable())
        ;

        $tester = self::getCommandTester($clientManager);

        $tester->execute([]);

        $tester->assertCommandIsSuccessful();
        $display = $tester->getDisplay();
        self::assertStringContainsString('26.01.2022', $display);
    }

    public function testEmpty(): void
    {
        $clientManager = $this->createMock(ClientManagerInterface::class);
        $clientManager
            ->expects(self::once())
            ->method('findOldest')
            ->willReturn(null)
        ;
        $clientManager
            ->expects(self::never())
            ->method('getLastAccess')
        ;

        $tester = self::getCommandTester($clientManager);

        $tester->execute([]);

        $tester->assertCommandIsSuccessful();
        self::assertStringContainsString('There is no any client.', $tester->getDisplay());
    }

    private static function getCommandTester(ClientManagerInterface $clientManager): CommandTester
    {
        $application = new Application();
        $application->add(new Oldest($clientManager));

        $command = $application->find('client:oldest');

        return new CommandTester($command);
    }
}
