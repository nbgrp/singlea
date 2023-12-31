<?php
// SPDX-License-Identifier: BSD-3-Clause

declare(strict_types=1);

namespace SingleA\Bundles\Singlea\Tests\Command\Client;

use PHPUnit\Framework\TestCase;
use SingleA\Bundles\Singlea\Command\Client\Register;
use SingleA\Bundles\Singlea\Service\Client\RegistrationResult;
use SingleA\Bundles\Singlea\Service\Client\RegistrationServiceInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Uid\UuidV6;

/**
 * @covers \SingleA\Bundles\Singlea\Command\Client\Register
 *
 * @internal
 */
final class RegisterTest extends TestCase
{
    public function testSuccessfulRegistration(): void
    {
        $registrationService = $this->createMock(RegistrationServiceInterface::class);
        $registrationService
            ->expects(self::once())
            ->method('register')
            ->with(['some' => ['input' => 'data']])
            ->willReturn(new RegistrationResult(
                UuidV6::fromString('1ec7e40b-c569-6eb0-8d6c-d98bed082582'),
                'secret',
                ['some' => ['output' => 'data']],
            ))
        ;

        $tester = self::getCommandTester($registrationService);

        $tester->execute(['registration-json' => '{"some": {"input": "data"}}']);

        $tester->assertCommandIsSuccessful();
        $display = $tester->getDisplay();
        self::assertStringContainsString('Client successfully registered.', $display);
        self::assertStringContainsString('"id": "4oTPRz6Ntkn5hcaYkCjhLu"', $display);
        self::assertStringContainsString('"output": "data"', $display);
    }

    public function testSuccessfulInteractiveRegistration(): void
    {
        $registrationService = $this->createMock(RegistrationServiceInterface::class);
        $registrationService
            ->expects(self::once())
            ->method('register')
            ->with(['some' => ['input' => 'data']])
            ->willReturn(new RegistrationResult(
                UuidV6::fromString('1ec7e40b-c569-6eb0-8d6c-d98bed082582'),
                'secret',
                ['some' => ['output' => 'data']],
            ))
        ;

        $tester = self::getCommandTester($registrationService);

        $tester->setInputs([<<<'JSON'
            {
              "some": {
                "input": "data"
              }
            }
            JSON]);
        $tester->execute([]);

        $tester->assertCommandIsSuccessful();
        $display = $tester->getDisplay();
        self::assertStringContainsString('Client successfully registered.', $display);
        self::assertStringContainsString('"id": "4oTPRz6Ntkn5hcaYkCjhLu"', $display);
        self::assertStringContainsString('"output": "data"', $display);
    }

    public function testEmptyJson(): void
    {
        $registrationService = $this->createMock(RegistrationServiceInterface::class);
        $registrationService
            ->expects(self::never())
            ->method('register')
        ;

        $tester = self::getCommandTester($registrationService);

        $tester->execute(['registration-json' => '']);

        self::assertSame(Command::FAILURE, $tester->getStatusCode());
        self::assertStringContainsString('The registration data JSON string does not specified.', $tester->getDisplay());
    }

    public function testInvalidJson(): void
    {
        $registrationService = $this->createMock(RegistrationServiceInterface::class);
        $registrationService
            ->expects(self::never())
            ->method('register')
        ;

        $tester = self::getCommandTester($registrationService);

        $tester->execute(['registration-json' => '{"some": "invalid']);

        self::assertSame(Command::FAILURE, $tester->getStatusCode());
        self::assertStringContainsString('Invalid JSON specified:', $tester->getDisplay());
    }

    public function testUnexpectedError(): void
    {
        $registrationService = $this->createMock(RegistrationServiceInterface::class);
        $registrationService
            ->expects(self::once())
            ->method('register')
            ->willThrowException(new \RuntimeException('some error'))
        ;

        $tester = self::getCommandTester($registrationService);

        $tester->execute(['registration-json' => '{"some": {"input": "data"}}']);

        self::assertSame(Command::FAILURE, $tester->getStatusCode());
        self::assertStringContainsString('some error', $tester->getDisplay());
    }

    private static function getCommandTester(RegistrationServiceInterface $registrationService): CommandTester
    {
        $application = new Application();
        $application->add(new Register($registrationService));

        $command = $application->find('client:register');

        return new CommandTester($command);
    }
}
