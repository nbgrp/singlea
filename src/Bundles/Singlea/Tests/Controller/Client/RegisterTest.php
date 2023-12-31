<?php
// SPDX-License-Identifier: BSD-3-Clause

declare(strict_types=1);

namespace SingleA\Bundles\Singlea\Tests\Controller\Client;

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use SingleA\Bundles\Singlea\Controller\Client\Register;
use SingleA\Bundles\Singlea\Service\Client\RegistrationResult;
use SingleA\Bundles\Singlea\Service\Client\RegistrationServiceInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Symfony\Component\Uid\UuidV6;

/**
 * @covers \SingleA\Bundles\Singlea\Controller\Client\Register
 *
 * @internal
 */
final class RegisterTest extends TestCase
{
    public function testSuccessfulRegistration(): void
    {
        $input = ['some' => ['input' => 'data']];

        $registrationService = $this->createMock(RegistrationServiceInterface::class);
        $registrationService
            ->expects(self::once())
            ->method('register')
            ->with($input)
            ->willReturn(new RegistrationResult(
                new UuidV6(),
                'secret',
                ['feature' => ['foo' => 'bar']],
            ))
        ;

        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects(self::once())
            ->method('info')
        ;

        $response = (new Register())(
            Request::create('', content: json_encode($input, \JSON_THROW_ON_ERROR)),
            $registrationService,
            $logger,
        );

        $data = json_decode($response->getContent(), true, flags: \JSON_THROW_ON_ERROR);

        self::assertArrayHasKey('client', $data);
        self::assertArrayHasKey('id', $data['client']);
        self::assertArrayHasKey('secret', $data['client']);

        self::assertArrayHasKey('feature', $data);
        self::assertSame(['foo' => 'bar'], $data['feature']);
    }

    public function testBadJsonRegistration(): void
    {
        $registrationService = $this->createMock(RegistrationServiceInterface::class);
        $registrationService
            ->expects(self::never())
            ->method('register')
        ;

        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects(self::once())
            ->method('warning')
        ;

        $controller = new Register();
        $request = Request::create('', content: '{"some":{"input":"data');

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Invalid JSON.');

        $controller($request, $registrationService, $logger);
    }

    public function testBadInputRegistration(): void
    {
        $registrationService = $this->createMock(RegistrationServiceInterface::class);
        $registrationService
            ->expects(self::once())
            ->method('register')
            ->willThrowException(new \DomainException('wrong input data'))
        ;

        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects(self::once())
            ->method('error')
        ;

        $controller = new Register();
        $request = Request::create('', content: '{"some":{"input":"data"}}');

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('wrong input data');

        $controller($request, $registrationService, $logger);
    }

    public function testFailedRegistration(): void
    {
        $registrationService = $this->createMock(RegistrationServiceInterface::class);
        $registrationService
            ->expects(self::once())
            ->method('register')
            ->willThrowException(new \RuntimeException('unexpected'))
        ;

        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects(self::once())
            ->method('critical')
        ;

        $controller = new Register();
        $request = Request::create('', content: '{"some":{"input":"data"}}');

        $this->expectException(ServiceUnavailableHttpException::class);
        $this->expectExceptionMessage('unexpected');

        $controller($request, $registrationService, $logger);
    }
}
