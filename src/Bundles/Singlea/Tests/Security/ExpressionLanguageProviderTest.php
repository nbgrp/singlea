<?php declare(strict_types=1);
// SPDX-License-Identifier: BSD-3-Clause

namespace SingleA\Bundles\Singlea\Tests\Security;

use PHPUnit\Framework\TestCase;
use SingleA\Bundles\Singlea\Security\ExpressionLanguageProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManager;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;
use Symfony\Component\Security\Core\Authorization\ExpressionLanguage;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

/**
 * @covers \SingleA\Bundles\Singlea\Security\ExpressionLanguageProvider
 *
 * @internal
 */
final class ExpressionLanguageProviderTest extends TestCase
{
    private ExpressionLanguage $expressionLanguage;

    /**
     * @dataProvider compileProvider
     */
    public function testCompile(string $expression, string $expected): void
    {
        self::assertSame($expected, $this->expressionLanguage->compile($expression, ['request']));
    }

    public function compileProvider(): \Generator
    {
        yield 'is_valid_signature' => [
            'expression' => 'is_valid_signature(request)',
            'expected' => '$auth_checker->isGranted("SINGLEA_SIGNATURE", $request)',
        ];

        yield 'is_valid_ticket' => [
            'expression' => 'is_valid_ticket(request)',
            'expected' => '$auth_checker->isGranted("SINGLEA_TICKET", $request)',
        ];

        yield 'is_valid_client_ip' => [
            'expression' => 'is_valid_client_ip(request)',
            'expected' => '$auth_checker->isGranted("CLIENT_IP", $request)',
        ];

        yield 'is_valid_registration_ip' => [
            'expression' => 'is_valid_registration_ip(request)',
            'expected' => '$auth_checker->isGranted("REGISTRATION_IP", $request)',
        ];

        yield 'is_valid_registration_ticket' => [
            'expression' => 'is_valid_registration_ticket(request)',
            'expected' => '$auth_checker->isGranted("REGISTRATION_TICKET", $request)',
        ];
    }

    /**
     * @dataProvider evaluateProvider
     */
    public function testEvaluate(string $expression, Voter $voter): void
    {
        self::assertTrue($this->expressionLanguage->evaluate($expression, [
            'auth_checker' => new AuthorizationChecker(new TokenStorage(), new AccessDecisionManager([$voter])),
            'request' => Request::create(''),
        ]));
    }

    public function evaluateProvider(): \Generator
    {
        yield 'is_valid_signature' => [
            'expression' => 'is_valid_signature(request)',
            'voter' => (function (): Voter {
                $voter = $this->createConfiguredMock(Voter::class, ['supportsAttribute' => true, 'supportsType' => true]);
                $voter
                    ->expects(self::once())
                    ->method('vote')
                    ->with(
                        self::isInstanceOf(TokenInterface::class),
                        self::isInstanceOf(Request::class),
                        ['SINGLEA_SIGNATURE'],
                    )
                    ->willReturn(VoterInterface::ACCESS_GRANTED)
                ;

                return $voter;
            })(),
        ];

        yield 'is_valid_ticket' => [
            'expression' => 'is_valid_ticket(request)',
            'voter' => (function (): Voter {
                $voter = $this->createConfiguredMock(Voter::class, ['supportsAttribute' => true, 'supportsType' => true]);
                $voter
                    ->expects(self::once())
                    ->method('vote')
                    ->with(
                        self::isInstanceOf(TokenInterface::class),
                        self::isInstanceOf(Request::class),
                        ['SINGLEA_TICKET'],
                    )
                    ->willReturn(VoterInterface::ACCESS_GRANTED)
                ;

                return $voter;
            })(),
        ];

        yield 'is_valid_client_ip' => [
            'expression' => 'is_valid_client_ip(request)',
            'voter' => (function (): Voter {
                $voter = $this->createConfiguredMock(Voter::class, ['supportsAttribute' => true, 'supportsType' => true]);
                $voter
                    ->expects(self::once())
                    ->method('vote')
                    ->with(
                        self::isInstanceOf(TokenInterface::class),
                        self::isInstanceOf(Request::class),
                        ['CLIENT_IP'],
                    )
                    ->willReturn(VoterInterface::ACCESS_GRANTED)
                ;

                return $voter;
            })(),
        ];

        yield 'is_valid_registration_ip' => [
            'expression' => 'is_valid_registration_ip(request)',
            'voter' => (function (): Voter {
                $voter = $this->createConfiguredMock(Voter::class, ['supportsAttribute' => true, 'supportsType' => true]);
                $voter
                    ->expects(self::once())
                    ->method('vote')
                    ->with(
                        self::isInstanceOf(TokenInterface::class),
                        self::isInstanceOf(Request::class),
                        ['REGISTRATION_IP'],
                    )
                    ->willReturn(VoterInterface::ACCESS_GRANTED)
                ;

                return $voter;
            })(),
        ];

        yield 'is_valid_registration_ticket' => [
            'expression' => 'is_valid_registration_ticket(request)',
            'voter' => (function (): Voter {
                $voter = $this->createConfiguredMock(Voter::class, ['supportsAttribute' => true, 'supportsType' => true]);
                $voter
                    ->expects(self::once())
                    ->method('vote')
                    ->with(
                        self::isInstanceOf(TokenInterface::class),
                        self::isInstanceOf(Request::class),
                        ['REGISTRATION_TICKET'],
                    )
                    ->willReturn(VoterInterface::ACCESS_GRANTED)
                ;

                return $voter;
            })(),
        ];
    }

    protected function setUp(): void
    {
        $this->expressionLanguage = new ExpressionLanguage();
        $this->expressionLanguage->registerProvider(new ExpressionLanguageProvider());
    }
}
