<?php
// SPDX-License-Identifier: BSD-3-Clause

declare(strict_types=1);

namespace SingleA\Bundles\Singlea\Tests\Service\Client;

use Jose\Component\Core\JWK;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use SingleA\Bundles\Singlea\FeatureConfig\Signature\SignatureConfig;
use SingleA\Bundles\Singlea\FeatureConfig\Signature\SignatureConfigInterface;
use SingleA\Bundles\Singlea\Service\Client\RegistrationService;
use SingleA\Contracts\FeatureConfig\FeatureConfigFactoryInterface;
use SingleA\Contracts\Persistence\ClientManagerInterface;
use SingleA\Contracts\Persistence\FeatureConfigManagerInterface;
use SingleA\Contracts\Tokenization\TokenizerConfigInterface;

/**
 * @covers \SingleA\Bundles\Singlea\Service\Client\RegistrationResult
 * @covers \SingleA\Bundles\Singlea\Service\Client\RegistrationService
 *
 * @internal
 */
final class RegistrationServiceTest extends TestCase
{
    private static string $uuidPattern = '/[[:xdigit:]]{8}-[[:xdigit:]]{4}-[[:xdigit:]]{4}-[[:xdigit:]]{4}-[[:xdigit:]]{12}/';

    public function testSuccessfulRegister(): void
    {
        $input = [
            'signature' => [
                'md-alg' => 'sha256',
            ],
            'token' => [
                '#' => 'jwt',
                'claims' => ['username', 'email'],
            ],
        ];

        $signatureConfig = $this->createStub(SignatureConfigInterface::class);
        $tokenizerConfig = $this->createStub(TokenizerConfigInterface::class);

        $configFactories = [
            (function (): FeatureConfigFactoryInterface {
                $fetcherFactory = $this->createMock(FeatureConfigFactoryInterface::class);
                $fetcherFactory
                    ->method('getKey')
                    ->willReturn('payload')
                ;
                $fetcherFactory
                    ->expects(self::never())
                    ->method('getHash')
                ;
                $fetcherFactory
                    ->expects(self::never())
                    ->method('create')
                ;

                return $fetcherFactory;
            })(),
            (function () use ($tokenizerConfig): FeatureConfigFactoryInterface {
                $tokenizerFactory = $this->createMock(FeatureConfigFactoryInterface::class);
                $tokenizerFactory
                    ->method('getKey')
                    ->willReturn('token')
                ;
                $tokenizerFactory
                    ->method('getHash')
                    ->willReturn('jwt')
                ;
                $tokenizerFactory
                    ->expects(self::once())
                    ->method('create')
                    ->with(
                        [
                            '#' => 'jwt',
                            'claims' => ['username', 'email'],
                        ],
                        self::anything(),
                    )
                    ->willReturnCallback(static function (array $input, mixed &$output = null) use ($tokenizerConfig): TokenizerConfigInterface {
                        $output['jwk'] = new JWK(['kty' => 'oct', 'k' => 'k-value']);

                        return $tokenizerConfig;
                    })
                ;

                return $tokenizerFactory;
            })(),
            (function () use ($signatureConfig): FeatureConfigFactoryInterface {
                $signatureFactory = $this->createMock(FeatureConfigFactoryInterface::class);
                $signatureFactory
                    ->method('getKey')
                    ->willReturn('signature')
                ;
                $signatureFactory
                    ->method('getHash')
                    ->willReturn('signature')
                ;
                $signatureFactory
                    ->expects(self::once())
                    ->method('create')
                    ->with(
                        [
                            'md-alg' => 'sha256',
                        ],
                        self::anything(),
                    )
                    ->willReturnCallback(static fn (): SignatureConfigInterface => $signatureConfig)
                ;

                return $signatureFactory;
            })(),
        ];

        $configManagers = [
            (function () use ($signatureConfig, $tokenizerConfig): FeatureConfigManagerInterface {
                $fetcherManager = $this->createMock(FeatureConfigManagerInterface::class);
                $fetcherManager
                    ->method('supports')
                    ->willReturnMap([
                        [$signatureConfig, false],
                        [$tokenizerConfig, false],
                    ])
                ;
                $fetcherManager
                    ->method('isRequired')
                    ->willReturn(false)
                ;
                $fetcherManager
                    ->expects(self::never())
                    ->method('persist')
                ;
                $fetcherManager
                    ->expects(self::never())
                    ->method('remove')
                ;

                return $fetcherManager;
            })(),
            (function () use ($signatureConfig, $tokenizerConfig): FeatureConfigManagerInterface {
                $tokenizerManager = $this->createMock(FeatureConfigManagerInterface::class);
                $tokenizerManager
                    ->method('supports')
                    ->willReturnMap([
                        [$signatureConfig, false],
                        [$tokenizerConfig, true],
                    ])
                ;
                $tokenizerManager
                    ->method('isRequired')
                    ->willReturn(false)
                ;
                $tokenizerManager
                    ->expects(self::once())
                    ->method('persist')
                    ->with(
                        self::matchesRegularExpression(self::$uuidPattern),
                        $tokenizerConfig,
                        self::callback(static fn (string $value): bool => mb_strlen($value, '8bit') === \SODIUM_CRYPTO_SECRETBOX_NONCEBYTES),
                    )
                ;
                $tokenizerManager
                    ->expects(self::never())
                    ->method('remove')
                ;

                return $tokenizerManager;
            })(),
            (function () use ($signatureConfig, $tokenizerConfig): FeatureConfigManagerInterface {
                $signatureManager = $this->createMock(FeatureConfigManagerInterface::class);
                $signatureManager
                    ->method('supports')
                    ->willReturnMap([
                        [$signatureConfig, true],
                        [$tokenizerConfig, false],
                    ])
                ;
                $signatureManager
                    ->method('isRequired')
                    ->willReturn(true)
                ;
                $signatureManager
                    ->expects(self::once())
                    ->method('persist')
                    ->with(
                        self::matchesRegularExpression(self::$uuidPattern),
                        $signatureConfig,
                        self::callback(static fn (string $value): bool => mb_strlen($value, '8bit') === \SODIUM_CRYPTO_SECRETBOX_NONCEBYTES),
                    )
                ;
                $signatureManager
                    ->expects(self::never())
                    ->method('remove')
                ;

                return $signatureManager;
            })(),
        ];

        $clientManager = $this->createMock(ClientManagerInterface::class);
        $clientManager
            ->expects(self::once())
            ->method('touch')
            ->with(self::matchesRegularExpression(self::$uuidPattern))
        ;
        $clientManager
            ->expects(self::never())
            ->method('remove')
        ;

        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects(self::exactly(4))
            ->method('debug')
        ;

        $service = new RegistrationService($configFactories, $configManagers, $clientManager, $logger);

        $result = $service->register($input);

        self::assertSame(32, \strlen($result->getSecret()));
        self::assertArrayHasKey('token', $result->getOutput());
        self::assertArrayNotHasKey('signature', $result->getOutput());
    }

    /**
     * @dataProvider provideFailedRegisterCases
     */
    public function testFailedRegister(
        array $input,
        iterable $configFactories,
        iterable $configManagers,
        ClientManagerInterface $clientManager,
        LoggerInterface $logger,
        string $expectedException,
        string $expectedMessage,
    ): void {
        $service = new RegistrationService($configFactories, $configManagers, $clientManager, $logger);

        $this->expectException($expectedException);
        $this->expectExceptionMessage($expectedMessage);

        $service->register($input);
    }

    public function provideFailedRegisterCases(): iterable
    {
        $clientManagerMocker = function (): ClientManagerInterface {
            $manager = $this->createMock(ClientManagerInterface::class);
            $manager
                ->expects(self::once())
                ->method('remove')
            ;

            return $manager;
        };

        yield 'Input element invalid type' => [
            'input' => [
                'unknown' => 'foo',
            ],
            'configFactories' => [],
            'configManagers' => [],
            'clientManager' => $clientManagerMocker(),
            'logger' => (function (): LoggerInterface {
                $logger = $this->createMock(LoggerInterface::class);
                $logger
                    ->expects(self::once())
                    ->method('error')
                ;
                $logger
                    ->expects(self::once())
                    ->method('debug')
                ;

                return $logger;
            })(),
            'expectedException' => \InvalidArgumentException::class,
            'expectedMessage' => 'Registration data in key "unknown" must be an array.',
        ];

        yield 'Unknown input' => [
            'input' => [
                'unknown' => ['foo' => 'bar'],
            ],
            'configFactories' => [],
            'configManagers' => [],
            'clientManager' => $clientManagerMocker(),
            'logger' => (function (): LoggerInterface {
                $logger = $this->createMock(LoggerInterface::class);
                $logger
                    ->expects(self::once())
                    ->method('error')
                ;
                $logger
                    ->expects(self::once())
                    ->method('debug')
                ;

                return $logger;
            })(),
            'expectedException' => \UnexpectedValueException::class,
            'expectedMessage' => 'Unsupported feature registration key "unknown" detected.',
        ];

        $signatureConfig = new SignatureConfig(\OPENSSL_ALGO_SHA256, '', 0);

        yield 'Not persisted' => [
            'input' => [
                'signature' => [],
            ],
            'configFactories' => [
                (function () use ($signatureConfig): FeatureConfigFactoryInterface {
                    $signatureFactory = $this->createMock(FeatureConfigFactoryInterface::class);
                    $signatureFactory
                        ->method('getKey')
                        ->willReturn('signature')
                    ;
                    $signatureFactory
                        ->method('getHash')
                        ->willReturn('signature')
                    ;
                    $signatureFactory
                        ->method('create')
                        ->willReturnCallback(static fn (): SignatureConfigInterface => $signatureConfig)
                    ;

                    return $signatureFactory;
                })(),
            ],
            'configManagers' => [],
            'clientManager' => $clientManagerMocker(),
            'logger' => (function (): LoggerInterface {
                $logger = $this->createMock(LoggerInterface::class);
                $logger
                    ->expects(self::once())
                    ->method('error')
                ;
                $logger
                    ->expects(self::exactly(2))
                    ->method('debug')
                ;

                return $logger;
            })(),
            'expectedException' => \RuntimeException::class,
            'expectedMessage' => 'Config SingleA\Bundles\Singlea\FeatureConfig\Signature\SignatureConfig cannot be persisted.',
        ];

        yield 'Required' => [
            'input' => [
                'signature' => [],
            ],
            'configFactories' => [
                (function () use ($signatureConfig): FeatureConfigFactoryInterface {
                    $signatureFactory = $this->createMock(FeatureConfigFactoryInterface::class);
                    $signatureFactory
                        ->method('getKey')
                        ->willReturn('signature')
                    ;
                    $signatureFactory
                        ->method('getHash')
                        ->willReturn('signature')
                    ;
                    $signatureFactory
                        ->method('create')
                        ->willReturnCallback(static fn (): SignatureConfigInterface => $signatureConfig)
                    ;

                    return $signatureFactory;
                })(),
            ],
            'configManagers' => [
                (function (): FeatureConfigManagerInterface {
                    $tokenizerManager = $this->createMock(FeatureConfigManagerInterface::class);
                    $tokenizerManager
                        ->method('supports')
                        ->willReturn(false)
                    ;
                    $tokenizerManager
                        ->method('isRequired')
                        ->willReturn(true)
                    ;
                    $tokenizerManager
                        ->expects(self::once())
                        ->method('remove')
                    ;

                    return $tokenizerManager;
                })(),
                (function (): FeatureConfigManagerInterface {
                    $signatureManager = $this->createMock(FeatureConfigManagerInterface::class);
                    $signatureManager
                        ->method('supports')
                        ->willReturn(true)
                    ;
                    $signatureManager
                        ->method('isRequired')
                        ->willReturn(true)
                    ;
                    $signatureManager
                        ->expects(self::once())
                        ->method('remove')
                    ;

                    return $signatureManager;
                })(),
            ],
            'clientManager' => $clientManagerMocker(),
            'logger' => (function (): LoggerInterface {
                $logger = $this->createMock(LoggerInterface::class);
                $logger
                    ->expects(self::once())
                    ->method('error')
                ;
                $logger
                    ->expects(self::exactly(3))
                    ->method('debug')
                ;

                return $logger;
            })(),
            'expectedException' => \DomainException::class,
            'expectedMessage' => 'Registration request does not contain all required config settings.',
        ];
    }
}
