<?php declare(strict_types=1);
// SPDX-License-Identifier: BSD-3-Clause

namespace SingleA\Bundles\Singlea\Tests\Security;

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use SingleA\Bundles\Singlea\EventListener\ClientListener;
use SingleA\Bundles\Singlea\EventListener\TicketListener;
use SingleA\Bundles\Singlea\FeatureConfig\ConfigRetrieverInterface;
use SingleA\Bundles\Singlea\FeatureConfig\Signature\SignatureConfigInterface;
use SingleA\Bundles\Singlea\Security\GrantedVoter;
use SingleA\Bundles\Singlea\Service\Client\RegistrationTicketManagerInterface;
use SingleA\Bundles\Singlea\Service\Signature\SignatureServiceInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Security\Core\Authentication\Token\NullToken;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

/**
 * @covers \SingleA\Bundles\Singlea\Security\GrantedVoter
 *
 * @internal
 */
final class GrantedVoterTest extends TestCase
{
    private static RegistrationTicketManagerInterface $registrationTicketManager;

    public static function setUpBeforeClass(): void
    {
        self::$registrationTicketManager = new class() implements RegistrationTicketManagerInterface {
            public function isValid(string $ticket): bool
            {
                return $ticket === 'valid-ticket';
            }
        };
    }

    /**
     * @dataProvider successfulValidateIpOrNetmaskProvider
     */
    public function testSuccessfulValidateIpOrNetmask(string $value, string $expected): void
    {
        self::assertSame($expected, GrantedVoter::validateIpOrNetmask($value));
    }

    public function successfulValidateIpOrNetmaskProvider(): \Generator
    {
        yield 'IPv4 host' => [
            'value' => '127.0.0.1',
            'expected' => '127.0.0.1',
        ];

        yield 'IPv4 subnet' => [
            'value' => '172.10.0.0/16',
            'expected' => '172.10.0.0/16',
        ];

        yield 'IPv6 host' => [
            'value' => '2001:db8:abcd:0012:0000:0000:0000:0001',
            'expected' => '2001:db8:abcd:0012:0000:0000:0000:0001',
        ];

        yield 'IPv6 subnet' => [
            'value' => '2001:db8:abcd:0012::0/64',
            'expected' => '2001:db8:abcd:0012::0/64',
        ];
    }

    /**
     * @dataProvider failedValidateIpOrNetmaskProvider
     */
    public function testFailedValidateIpOrNetmask(string $value, string $expectedMessage): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage($expectedMessage);

        GrantedVoter::validateIpOrNetmask($value);
    }

    public function failedValidateIpOrNetmaskProvider(): \Generator
    {
        yield 'Zero mask' => [
            'value' => '127.0.0.1/0',
            'expectedMessage' => 'Invalid network mask "0" for value "127.0.0.1/0".',
        ];

        yield 'Negative IPv4 mask' => [
            'value' => '127.0.0.1/-12',
            'expectedMessage' => 'Invalid network mask "-12" for value "127.0.0.1/-12".',
        ];

        yield 'Invalid IPv4 mask' => [
            'value' => '127.0.0.1/33',
            'expectedMessage' => 'Invalid network mask "33" for value "127.0.0.1/33".',
        ];

        yield 'Invalid IPv6 mask' => [
            'value' => '2001:db8::/256',
            'expectedMessage' => 'Invalid network mask "256" for value "2001:db8::/256".',
        ];

        yield 'Invalid type mask' => [
            'value' => '127.0.0.1/foo',
            'expectedMessage' => 'Invalid network mask "foo" for value "127.0.0.1/foo".',
        ];

        yield 'Invalid IP type' => [
            'value' => 'one-two-seven-point-zero-point-zero-point-one',
            'expectedMessage' => 'Invalid IP address "one-two-seven-point-zero-point-zero-point-one" for value "one-two-seven-point-zero-point-zero-point-one".',
        ];

        yield 'Invalid IPv4 value' => [
            'value' => '127.0.0.355',
            'expectedMessage' => 'Invalid IP address "127.0.0.355" for value "127.0.0.355".',
        ];

        yield 'Invalid IPv4 value with mask' => [
            'value' => '127.0.0.355/32',
            'expectedMessage' => 'Invalid IP address "127.0.0.355" for value "127.0.0.355/32".',
        ];

        yield 'Invalid IPv6 value' => [
            'value' => '2001:db8:abcd:0012:efgh:0000:0000:0001',
            'expectedMessage' => 'Invalid IP address "2001:db8:abcd:0012:efgh:0000:0000:0001" for value "2001:db8:abcd:0012:efgh:0000:0000:0001".',
        ];

        yield 'Invalid IPv6 value with mask' => [
            'value' => '2001:db8:defg::/64',
            'expectedMessage' => 'Invalid IP address "2001:db8:defg::" for value "2001:db8:defg::/64".',
        ];
    }

    /**
     * @dataProvider grantedVoteProvider
     */
    public function testGrantedVote(
        ?string $trustedClients,
        ?string $trustedRegistrars,
        ConfigRetrieverInterface $configRetriever,
        SignatureServiceInterface $signatureService,
        mixed $request,
        array $attributes,
    ): void {
        $voter = new GrantedVoter(
            $trustedClients,
            $trustedRegistrars,
            'X-Registration-Ticket',
            $configRetriever,
            $signatureService,
            self::$registrationTicketManager,
            null,
        );

        self::assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote(new NullToken(), $request, $attributes));
    }

    public function grantedVoteProvider(): \Generator
    {
        $config = $this->createStub(SignatureConfigInterface::class);

        yield 'Signature' => [
            'trustedClients' => null,
            'trustedRegistrars' => null,
            'configRetriever' => (function () use ($config): ConfigRetrieverInterface {
                $configRetriever = $this->createMock(ConfigRetrieverInterface::class);
                $configRetriever
                    ->expects(self::once())
                    ->method('exists')
                    ->with(SignatureConfigInterface::class, 'client')
                    ->willReturn(true)
                ;
                $configRetriever
                    ->expects(self::once())
                    ->method('find')
                    ->with(SignatureConfigInterface::class, 'client', 'secret')
                    ->willReturn($config)
                ;

                return $configRetriever;
            })(),
            'signatureService' => (function () use ($config): SignatureServiceInterface {
                $signatureService = $this->createMock(SignatureServiceInterface::class);
                $signatureService
                    ->expects(self::once())
                    ->method('check')
                    ->with(
                        self::isInstanceOf(Request::class),
                        $config,
                    )
                ;

                return $signatureService;
            })(),
            'request' => (static function (): Request {
                $request = Request::create('');
                $request->attributes->add([
                    ClientListener::CLIENT_ID_ATTRIBUTE => 'client',
                    ClientListener::SECRET_ATTRIBUTE => 'secret',
                ]);

                return $request;
            })(),
            'attributes' => ['SINGLEA_SIGNATURE'],
        ];

        yield 'Ticket' => [
            'trustedClients' => null,
            'trustedRegistrars' => null,
            'configRetriever' => $this->createStub(ConfigRetrieverInterface::class),
            'signatureService' => $this->createStub(SignatureServiceInterface::class),
            'request' => (static function (): Request {
                $request = Request::create('');
                $request->attributes->add([
                    TicketListener::TICKET_ATTRIBUTE => 'ticket',
                ]);

                return $request;
            })(),
            'attributes' => ['SINGLEA_TICKET'],
        ];

        yield 'Client IPv4' => [
            'trustedClients' => '192.168.0.1,192.168.0.1/24',
            'trustedRegistrars' => null,
            'configRetriever' => $this->createStub(ConfigRetrieverInterface::class),
            'signatureService' => $this->createStub(SignatureServiceInterface::class),
            'request' => Request::create('', server: ['REMOTE_ADDR' => '192.168.0.22']),
            'attributes' => ['CLIENT_IP'],
        ];

        yield 'Client IPv6' => [
            'trustedClients' => '2001:db8:abcd:0012::0/64,2aa1:db8:abcd:0012:0000:0000:0000:0001',
            'trustedRegistrars' => null,
            'configRetriever' => $this->createStub(ConfigRetrieverInterface::class),
            'signatureService' => $this->createStub(SignatureServiceInterface::class),
            'request' => Request::create('', server: ['REMOTE_ADDR' => '2001:db8:abcd:0012:0000:0000:0000:0001']),
            'attributes' => ['CLIENT_IP'],
        ];

        yield 'Client IP (REMOTE_ADDR)' => [
            'trustedClients' => 'REMOTE_ADDR',
            'trustedRegistrars' => null,
            'configRetriever' => $this->createStub(ConfigRetrieverInterface::class),
            'signatureService' => $this->createStub(SignatureServiceInterface::class),
            'request' => Request::create('', server: ['REMOTE_ADDR' => '172.0.0.250']),
            'attributes' => ['CLIENT_IP'],
        ];

        yield 'Client IP (without IPs)' => [
            'trustedClients' => null,
            'trustedRegistrars' => null,
            'configRetriever' => $this->createStub(ConfigRetrieverInterface::class),
            'signatureService' => $this->createStub(SignatureServiceInterface::class),
            'request' => Request::create('', server: ['REMOTE_ADDR' => '127.0.0.111']),
            'attributes' => ['CLIENT_IP'],
        ];

        yield 'Registration IPv4' => [
            'trustedClients' => null,
            'trustedRegistrars' => '192.168.1.1,192.168.1.1/20',
            'configRetriever' => $this->createStub(ConfigRetrieverInterface::class),
            'signatureService' => $this->createStub(SignatureServiceInterface::class),
            'request' => Request::create('', server: ['REMOTE_ADDR' => '192.168.10.1']),
            'attributes' => ['REGISTRATION_IP'],
        ];

        yield 'Registration IPv6' => [
            'trustedClients' => null,
            'trustedRegistrars' => '2001:db8:abcd:12::0/80,2aa1:db8:abcd:00ff:0000:0000:0000:0001',
            'configRetriever' => $this->createStub(ConfigRetrieverInterface::class),
            'signatureService' => $this->createStub(SignatureServiceInterface::class),
            'request' => Request::create('', server: ['REMOTE_ADDR' => '2001:db8:abcd:0012:0000:0000:0002:0002']),
            'attributes' => ['REGISTRATION_IP'],
        ];

        yield 'Registration IP (REMOTE_ADDR)' => [
            'trustedClients' => null,
            'trustedRegistrars' => 'REMOTE_ADDR',
            'configRetriever' => $this->createStub(ConfigRetrieverInterface::class),
            'signatureService' => $this->createStub(SignatureServiceInterface::class),
            'request' => Request::create('', server: ['REMOTE_ADDR' => '172.0.10.150']),
            'attributes' => ['REGISTRATION_IP'],
        ];

        yield 'Registration IP (without IPs)' => [
            'trustedClients' => null,
            'trustedRegistrars' => null,
            'configRetriever' => $this->createStub(ConfigRetrieverInterface::class),
            'signatureService' => $this->createStub(SignatureServiceInterface::class),
            'request' => Request::create('', server: ['REMOTE_ADDR' => '127.0.0.222']),
            'attributes' => ['REGISTRATION_IP'],
        ];

        yield 'Registration ticket' => [
            'trustedClients' => null,
            'trustedRegistrars' => null,
            'configRetriever' => $this->createStub(ConfigRetrieverInterface::class),
            'signatureService' => $this->createStub(SignatureServiceInterface::class),
            'request' => Request::create('', server: ['HTTP_X_REGISTRATION_TICKET' => 'valid-ticket']),
            'attributes' => ['REGISTRATION_TICKET'],
        ];
    }

    /**
     * @dataProvider abstainVoteProvider
     */
    public function testAbstainVote(
        ConfigRetrieverInterface $configRetriever,
        SignatureServiceInterface $signatureService,
        ?LoggerInterface $logger,
        mixed $request,
        array $attributes,
    ): void {
        $voter = new GrantedVoter(
            '192.168.0.1,192.168.0.1/24',
            '192.168.1.1,192.168.1.1/20',
            'X-Registration-Ticket',
            $configRetriever,
            $signatureService,
            self::$registrationTicketManager,
            $logger,
        );

        self::assertSame(VoterInterface::ACCESS_ABSTAIN, $voter->vote(new NullToken(), $request, $attributes));
    }

    public function abstainVoteProvider(): \Generator
    {
        yield 'Unsupported attribute' => [
            'configRetriever' => $this->createStub(ConfigRetrieverInterface::class),
            'signatureService' => $this->createStub(SignatureServiceInterface::class),
            'logger' => null,
            'request' => null,
            'attributes' => ['UNKNOWN'],
        ];

        yield 'Unsupported subject' => [
            'configRetriever' => $this->createStub(ConfigRetrieverInterface::class),
            'signatureService' => $this->createStub(SignatureServiceInterface::class),
            'logger' => null,
            'request' => new \stdClass(),
            'attributes' => ['SINGLEA_SIGNATURE'],
        ];

        yield 'Client without signature support' => [
            'configRetriever' => (function (): ConfigRetrieverInterface {
                $configRetriever = $this->createMock(ConfigRetrieverInterface::class);
                $configRetriever
                    ->expects(self::once())
                    ->method('exists')
                    ->with(SignatureConfigInterface::class, 'client')
                    ->willReturn(false)
                ;

                return $configRetriever;
            })(),
            'signatureService' => $this->createStub(SignatureServiceInterface::class),
            'logger' => null,
            'request' => (static function (): Request {
                $request = Request::create('');
                $request->attributes->add([
                    ClientListener::CLIENT_ID_ATTRIBUTE => 'client',
                    ClientListener::SECRET_ATTRIBUTE => 'secret',
                ]);

                return $request;
            })(),
            'attributes' => ['SINGLEA_SIGNATURE'],
        ];
    }

    /**
     * @dataProvider deniedVoteProvider
     */
    public function testDeniedVote(
        ConfigRetrieverInterface $configRetriever,
        SignatureServiceInterface $signatureService,
        ?LoggerInterface $logger,
        mixed $request,
        array $attributes,
    ): void {
        $voter = new GrantedVoter(
            '192.168.0.1,192.168.0.1/24,2bb1:db8:abcd:0011::0/80,2aa1:db8:abcd:aacc:0000:0000:0000:0001',
            '192.168.1.1,192.168.1.1/20,2bb1:db8:abcd:12::0/80,2aa1:db8:abcd:bbee:0000:0000:0000:0001',
            'X-Registration-Ticket',
            $configRetriever,
            $signatureService,
            self::$registrationTicketManager,
            $logger,
        );

        self::assertSame(VoterInterface::ACCESS_DENIED, $voter->vote(new NullToken(), $request, $attributes));
    }

    public function deniedVoteProvider(): \Generator
    {
        $config = $this->createStub(SignatureConfigInterface::class);

        yield 'Invalid signature' => [
            'configRetriever' => (function () use ($config): ConfigRetrieverInterface {
                $configRetriever = $this->createMock(ConfigRetrieverInterface::class);
                $configRetriever
                    ->expects(self::once())
                    ->method('exists')
                    ->with(SignatureConfigInterface::class, 'client')
                    ->willReturn(true)
                ;
                $configRetriever
                    ->expects(self::once())
                    ->method('find')
                    ->with(SignatureConfigInterface::class, 'client', 'secret')
                    ->willReturn($config)
                ;

                return $configRetriever;
            })(),
            'signatureService' => (function () use ($config): SignatureServiceInterface {
                $signatureService = $this->createMock(SignatureServiceInterface::class);
                $signatureService
                    ->expects(self::once())
                    ->method('check')
                    ->with(
                        self::isInstanceOf(Request::class),
                        $config,
                    )
                    ->willThrowException(new \RuntimeException())
                ;

                return $signatureService;
            })(),
            'logger' => (function (): LoggerInterface {
                $logger = $this->createMock(LoggerInterface::class);
                $logger
                    ->expects(self::once())
                    ->method('notice')
                ;

                return $logger;
            })(),
            'request' => (static function (): Request {
                $request = Request::create('');
                $request->attributes->add([
                    ClientListener::CLIENT_ID_ATTRIBUTE => 'client',
                    ClientListener::SECRET_ATTRIBUTE => 'secret',
                ]);

                return $request;
            })(),
            'attributes' => ['SINGLEA_SIGNATURE'],
        ];

        yield 'Invalid ticket' => [
            'configRetriever' => $this->createStub(ConfigRetrieverInterface::class),
            'signatureService' => $this->createStub(SignatureServiceInterface::class),
            'logger' => null,
            'request' => Request::create(''),
            'attributes' => ['SINGLEA_TICKET'],
        ];

        yield 'Invalid client IPv4' => [
            'configRetriever' => $this->createStub(ConfigRetrieverInterface::class),
            'signatureService' => $this->createStub(SignatureServiceInterface::class),
            'logger' => null,
            'request' => Request::create('', server: ['REMOTE_ADDR' => '127.0.0.1']),
            'attributes' => ['CLIENT_IP'],
        ];

        yield 'Invalid client IPv4 (netmask)' => [
            'configRetriever' => $this->createStub(ConfigRetrieverInterface::class),
            'signatureService' => $this->createStub(SignatureServiceInterface::class),
            'logger' => null,
            'request' => Request::create('', server: ['REMOTE_ADDR' => '192.168.1.1']),
            'attributes' => ['CLIENT_IP'],
        ];

        yield 'Invalid client IPv6' => [
            'configRetriever' => $this->createStub(ConfigRetrieverInterface::class),
            'signatureService' => $this->createStub(SignatureServiceInterface::class),
            'logger' => null,
            'request' => Request::create('', server: ['REMOTE_ADDR' => '2aa1:db8:abcd:0011:0000:0000:0000:0001']),
            'attributes' => ['CLIENT_IP'],
        ];

        yield 'Invalid client IPv6 (netmask)' => [
            'configRetriever' => $this->createStub(ConfigRetrieverInterface::class),
            'signatureService' => $this->createStub(SignatureServiceInterface::class),
            'logger' => null,
            'request' => Request::create('', server: ['REMOTE_ADDR' => '2001:db8:abcd:aacc:0000:0000:0000:0001']),
            'attributes' => ['CLIENT_IP'],
        ];

        yield 'Invalid registration IPv4' => [
            'configRetriever' => $this->createStub(ConfigRetrieverInterface::class),
            'signatureService' => $this->createStub(SignatureServiceInterface::class),
            'logger' => null,
            'request' => Request::create('', server: ['REMOTE_ADDR' => '192.168.62.1']),
            'attributes' => ['REGISTRATION_IP'],
        ];

        yield 'Invalid registration IPv4 (netmask)' => [
            'configRetriever' => $this->createStub(ConfigRetrieverInterface::class),
            'signatureService' => $this->createStub(SignatureServiceInterface::class),
            'logger' => null,
            'request' => Request::create('', server: ['REMOTE_ADDR' => '192.168.62.1']),
            'attributes' => ['REGISTRATION_IP'],
        ];

        yield 'Invalid registration IPv6' => [
            'configRetriever' => $this->createStub(ConfigRetrieverInterface::class),
            'signatureService' => $this->createStub(SignatureServiceInterface::class),
            'logger' => null,
            'request' => Request::create('', server: ['REMOTE_ADDR' => '2aa1:db8:abcd:bbee:0000:0000:0000:0002']),
            'attributes' => ['REGISTRATION_IP'],
        ];

        yield 'Invalid registration IPv6 (netmask)' => [
            'configRetriever' => $this->createStub(ConfigRetrieverInterface::class),
            'signatureService' => $this->createStub(SignatureServiceInterface::class),
            'logger' => null,
            'request' => Request::create('', server: ['REMOTE_ADDR' => '2bb1:db8:abcd:13:0000:0000:0000:0001']),
            'attributes' => ['REGISTRATION_IP'],
        ];

        yield 'No registration ticket manager' => [
            'configRetriever' => $this->createStub(ConfigRetrieverInterface::class),
            'signatureService' => $this->createStub(SignatureServiceInterface::class),
            'logger' => null,
            'request' => Request::create(''),
            'attributes' => ['REGISTRATION_TICKET'],
        ];

        yield 'No registration ticket header' => [
            'configRetriever' => $this->createStub(ConfigRetrieverInterface::class),
            'signatureService' => $this->createStub(SignatureServiceInterface::class),
            'logger' => null,
            'request' => Request::create(''),
            'attributes' => ['REGISTRATION_TICKET'],
        ];

        yield 'Empty registration ticket header' => [
            'configRetriever' => $this->createStub(ConfigRetrieverInterface::class),
            'signatureService' => $this->createStub(SignatureServiceInterface::class),
            'logger' => null,
            'request' => Request::create('', server: ['HTTP_X_REGISTRATION_TICKET' => '']),
            'attributes' => ['REGISTRATION_TICKET'],
        ];

        yield 'Invalid registration ticket' => [
            'configRetriever' => $this->createStub(ConfigRetrieverInterface::class),
            'signatureService' => $this->createStub(SignatureServiceInterface::class),
            'logger' => null,
            'request' => Request::create('', server: ['HTTP_X_REGISTRATION_TICKET' => 'invalid-ticket']),
            'attributes' => ['REGISTRATION_TICKET'],
        ];
    }

    public function testDeniedVoteWithoutRegistrationTicketManager(): void {
        $voter = new GrantedVoter(
            null,
            null,
            'X-Registration-Ticket',
            $this->createStub(ConfigRetrieverInterface::class),
            $this->createStub(SignatureServiceInterface::class),
            null,
            null,
        );

        self::assertSame(VoterInterface::ACCESS_DENIED, $voter->vote(new NullToken(), Request::create(''), ['REGISTRATION_TICKET']));
    }

    /**
     * @dataProvider invalidVoteProvider
     */
    public function testInvalidVote(Request $request, string $expectedMessage): void {
        $voter = new GrantedVoter(
            null,
            null,
            'X-Registration-Ticket',
            $this->createStub(ConfigRetrieverInterface::class),
            $this->createStub(SignatureServiceInterface::class),
        );

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage($expectedMessage);

        $voter->vote(new NullToken(), $request, ['SINGLEA_SIGNATURE']);
    }

    public function invalidVoteProvider(): \Generator
    {
        yield 'No client id' => [
            'request' => Request::create(''),
            'expectedMessage' => 'Request does not contain client_id.',
        ];

        yield 'No secret' => [
            'request' => (static function (): Request {
                $request = Request::create('');
                $request->attributes->add([
                    ClientListener::CLIENT_ID_ATTRIBUTE => 'client',
                ]);

                return $request;
            })(),
            'expectedMessage' => 'Request does not contain client secret.',
        ];
    }
}
