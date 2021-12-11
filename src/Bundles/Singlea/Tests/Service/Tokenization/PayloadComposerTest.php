<?php declare(strict_types=1);
// SPDX-License-Identifier: BSD-3-Clause

namespace SingleA\Bundles\Singlea\Tests\Service\Tokenization;

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use SingleA\Bundles\Singlea\Service\Tokenization\PayloadComposer;
use SingleA\Bundles\Singlea\Tests\Service\TestFetcherConfig;
use SingleA\Bundles\Singlea\Tests\Service\TestTokenizerConfig;
use SingleA\Contracts\PayloadFetcher\FetcherConfigInterface;
use SingleA\Contracts\PayloadFetcher\FetcherInterface;
use SingleA\Contracts\Tokenization\TokenizerConfigInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * @covers \SingleA\Bundles\Singlea\Event\PayloadComposeEvent
 * @covers \SingleA\Bundles\Singlea\Service\Tokenization\PayloadComposer
 *
 * @internal
 */
final class PayloadComposerTest extends TestCase
{
    /**
     * @dataProvider composeProvider
     */
    public function testCompose(
        array $payloadFetchers,
        array $userAttributes,
        TokenizerConfigInterface $tokenizerConfig,
        ?FetcherConfigInterface $fetcherConfig,
        LoggerInterface $logger,
        array $expected,
    ): void {
        $composer = new PayloadComposer($payloadFetchers, new EventDispatcher(), $logger);

        self::assertSame($expected, $composer->compose($userAttributes, $tokenizerConfig, $fetcherConfig));
    }

    public function composeProvider(): \Generator
    {
        yield 'No fetcher' => [
            'payloadFetchers' => [],
            'userAttributes' => [
                'username' => 'tester',
                'email' => [
                    'tester@example.test',
                    'tester@singlea.test',
                ],
                'role' => 'ROLE_FOO',
            ],
            'tokenizerConfig' => new TestTokenizerConfig(null, ['username', 'email[]', 'role[]', 'unknown', null]),
            'fetcherConfig' => null,
            'logger' => (function (): LoggerInterface {
                $logger = $this->createMock(LoggerInterface::class);
                $logger
                    ->expects(self::never())
                    ->method('debug')
                ;

                return $logger;
            })(),
            'expected' => [
                'username' => 'tester',
                'email[]' => [
                    'tester@example.test',
                    'tester@singlea.test',
                ],
                'role[]' => ['ROLE_FOO'],
            ],
        ];

        $fetcherConfig = new TestFetcherConfig('endpoint', ['username', 'email', 'role'], null);

        yield 'Fetch with override' => [
            'payloadFetchers' => [
                (function () use ($fetcherConfig): FetcherInterface {
                    $fetcher = $this->createMock(FetcherInterface::class);
                    $fetcher
                        ->expects(self::once())
                        ->method('supports')
                        ->with($fetcherConfig)
                        ->willReturn(false)
                    ;
                    $fetcher
                        ->expects(self::never())
                        ->method('fetch')
                    ;

                    return $fetcher;
                })(),
                (function () use ($fetcherConfig): FetcherInterface {
                    $fetcher = $this->createMock(FetcherInterface::class);
                    $fetcher
                        ->expects(self::once())
                        ->method('supports')
                        ->with($fetcherConfig)
                        ->willReturn(true)
                    ;
                    $fetcher
                        ->expects(self::once())
                        ->method('fetch')
                        ->with(
                            [
                                'username' => 'tester',
                                'email' => 'tester@example.test',
                                'role' => 'ROLE_FOO',
                            ],
                            $fetcherConfig,
                        )
                        ->willReturn([
                            'username' => 'modified-tester',
                            'extra' => 'bar',
                        ])
                    ;

                    return $fetcher;
                })(),
            ],
            'userAttributes' => [
                'username' => 'tester',
                'email' => [
                    'tester@example.test',
                    'tester@singlea.test',
                ],
                'role' => 'ROLE_FOO',
            ],
            'tokenizerConfig' => new TestTokenizerConfig(null, ['username', 'role']),
            'fetcherConfig' => $fetcherConfig,
            'logger' => (function (): LoggerInterface {
                $logger = $this->createMock(LoggerInterface::class);
                $logger
                    ->expects(self::once())
                    ->method('debug')
                ;

                return $logger;
            })(),
            'expected' => [
                'username' => 'modified-tester',
                'role' => 'ROLE_FOO',
                'extra' => 'bar',
            ],
        ];
    }

    public function testUnsupportedFetcherConfig(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects(self::never())
            ->method('debug')
        ;

        $composer = new PayloadComposer([], new EventDispatcher(), $logger);

        $tokenizerConfig = new TestTokenizerConfig(null, null);
        $fetcherConfig = new TestFetcherConfig('', null, null);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Payload fetcher for config of type SingleA\Bundles\Singlea\Tests\Service\TestFetcherConfig not configured.');

        $composer->compose([], $tokenizerConfig, $fetcherConfig);
    }
}
