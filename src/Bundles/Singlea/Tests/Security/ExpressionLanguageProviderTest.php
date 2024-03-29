<?php
// SPDX-License-Identifier: BSD-3-Clause

declare(strict_types=1);

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
     * @dataProvider provideCompileCases
     */
    public function testCompile(string $expression, string $expected): void
    {
        self::assertSame($expected, $this->expressionLanguage->compile($expression, ['request']));
    }

    public function provideCompileCases(): iterable
    {
        yield 'is_valid_signature' => [
            'expression' => 'is_valid_signature()',
            'expected' => '$auth_checker->isGranted("SINGLEA_SIGNATURE")',
        ];

        yield 'is_valid_ticket' => [
            'expression' => 'is_valid_ticket()',
            'expected' => '$auth_checker->isGranted("SINGLEA_TICKET")',
        ];

        yield 'is_valid_client_ip' => [
            'expression' => 'is_valid_client_ip()',
            'expected' => '$auth_checker->isGranted("CLIENT_IP")',
        ];

        yield 'is_valid_registration_ip' => [
            'expression' => 'is_valid_registration_ip()',
            'expected' => '$auth_checker->isGranted("REGISTRATION_IP")',
        ];

        yield 'is_valid_registration_ticket' => [
            'expression' => 'is_valid_registration_ticket()',
            'expected' => '$auth_checker->isGranted("REGISTRATION_TICKET")',
        ];
    }

    /**
     * @dataProvider provideEvaluateCases
     */
    public function testEvaluate(string $expression, Voter $voter): void
    {
        self::assertTrue($this->expressionLanguage->evaluate($expression, [
            'auth_checker' => new AuthorizationChecker(new TokenStorage(), new AccessDecisionManager([$voter])),
            'request' => Request::create(''),
        ]));
    }

    public function provideEvaluateCases(): iterable
    {
        yield 'is_valid_signature' => [
            'expression' => 'is_valid_signature()',
            'voter' => (function (): Voter {
                $voter = $this->createConfiguredMock(Voter::class, ['supportsAttribute' => true, 'supportsType' => true]);
                $voter
                    ->expects(self::once())
                    ->method('vote')
                    ->with(
                        self::isInstanceOf(TokenInterface::class),
                        self::isNull(),
                        ['SINGLEA_SIGNATURE'],
                    )
                    ->willReturn(VoterInterface::ACCESS_GRANTED)
                ;

                return $voter;
            })(),
        ];

        yield 'is_valid_ticket' => [
            'expression' => 'is_valid_ticket()',
            'voter' => (function (): Voter {
                $voter = $this->createConfiguredMock(Voter::class, ['supportsAttribute' => true, 'supportsType' => true]);
                $voter
                    ->expects(self::once())
                    ->method('vote')
                    ->with(
                        self::isInstanceOf(TokenInterface::class),
                        self::isNull(),
                        ['SINGLEA_TICKET'],
                    )
                    ->willReturn(VoterInterface::ACCESS_GRANTED)
                ;

                return $voter;
            })(),
        ];

        yield 'is_valid_client_ip' => [
            'expression' => 'is_valid_client_ip()',
            'voter' => (function (): Voter {
                $voter = $this->createConfiguredMock(Voter::class, ['supportsAttribute' => true, 'supportsType' => true]);
                $voter
                    ->expects(self::once())
                    ->method('vote')
                    ->with(
                        self::isInstanceOf(TokenInterface::class),
                        self::isNull(),
                        ['CLIENT_IP'],
                    )
                    ->willReturn(VoterInterface::ACCESS_GRANTED)
                ;

                return $voter;
            })(),
        ];

        yield 'is_valid_registration_ip' => [
            'expression' => 'is_valid_registration_ip()',
            'voter' => (function (): Voter {
                $voter = $this->createConfiguredMock(Voter::class, ['supportsAttribute' => true, 'supportsType' => true]);
                $voter
                    ->expects(self::once())
                    ->method('vote')
                    ->with(
                        self::isInstanceOf(TokenInterface::class),
                        self::isNull(),
                        ['REGISTRATION_IP'],
                    )
                    ->willReturn(VoterInterface::ACCESS_GRANTED)
                ;

                return $voter;
            })(),
        ];

        yield 'is_valid_registration_ticket' => [
            'expression' => 'is_valid_registration_ticket()',
            'voter' => (function (): Voter {
                $voter = $this->createConfiguredMock(Voter::class, ['supportsAttribute' => true, 'supportsType' => true]);
                $voter
                    ->expects(self::once())
                    ->method('vote')
                    ->with(
                        self::isInstanceOf(TokenInterface::class),
                        self::isNull(),
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
