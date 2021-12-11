<?php declare(strict_types=1);
// SPDX-License-Identifier: BSD-3-Clause

namespace SingleA\Bundles\Singlea\Tests\Command\User;

use PHPUnit\Framework\TestCase;
use SingleA\Bundles\Singlea\Command\User\Logout;
use SingleA\Bundles\Singlea\Service\UserAttributes\UserAttributesManagerInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @covers \SingleA\Bundles\Singlea\Command\User\Logout
 *
 * @internal
 */
final class LogoutTest extends TestCase
{
    public function testSuccessfulExecute(): void
    {
        $userAttributesManager = $this->createMock(UserAttributesManagerInterface::class);
        $userAttributesManager
            ->expects(self::once())
            ->method('removeByUser')
            ->with('tester')
            ->willReturn(true)
        ;

        $tester = self::getCommandTester($userAttributesManager);

        $tester->execute(['identifier' => 'tester']);

        $tester->assertCommandIsSuccessful();
        self::assertStringContainsString('The user is fully logged out.', $tester->getDisplay());
    }

    public function testSuccessfulInteractiveExecute(): void
    {
        $userAttributesManager = $this->createMock(UserAttributesManagerInterface::class);
        $userAttributesManager
            ->expects(self::once())
            ->method('removeByUser')
            ->with('tester')
            ->willReturn(true)
        ;

        $tester = self::getCommandTester($userAttributesManager);

        $tester->setInputs(['tester']);
        $tester->execute([]);

        $tester->assertCommandIsSuccessful();
        self::assertStringContainsString('The user is fully logged out.', $tester->getDisplay());
    }

    public function testFailedExecute(): void
    {
        $userAttributesManager = $this->createMock(UserAttributesManagerInterface::class);
        $userAttributesManager
            ->expects(self::once())
            ->method('removeByUser')
            ->with('tester')
            ->willReturn(false)
        ;

        $tester = self::getCommandTester($userAttributesManager);

        $tester->setInputs(['tester']);
        $tester->execute([]);

        self::assertSame(Command::FAILURE, $tester->getStatusCode());
        self::assertStringContainsString('The user is not fully logged out. See logs for details.', $tester->getDisplay());
    }

    private static function getCommandTester(UserAttributesManagerInterface $userAttributesManager): CommandTester
    {
        $application = new Application();
        $application->add(new Logout($userAttributesManager));

        $command = $application->find('user:logout');

        return new CommandTester($command);
    }
}
