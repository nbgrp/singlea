<?php
// SPDX-License-Identifier: BSD-3-Clause

declare(strict_types=1);

namespace SingleA\Bundles\JwtFetcher\Tests\DependencyInjection;

use PHPUnit\Framework\TestCase;
use SingleA\Bundles\JwtFetcher\DependencyInjection\Configuration;
use Symfony\Component\Config\Definition\Processor;

/**
 * @covers \SingleA\Bundles\JwtFetcher\DependencyInjection\Configuration
 *
 * @internal
 */
final class ConfigurationTest extends TestCase
{
    private Processor $processor;

    /**
     * @dataProvider provideConfigurationCases
     */
    public function testConfiguration(array $config, array $expected): void
    {
        self::assertSame($expected, $this->processor->processConfiguration(new Configuration(), [$config]));
    }

    public function provideConfigurationCases(): iterable
    {
        yield 'Default configuration' => [
            'config' => [],
            'expected' => [
                'https_only' => true,
            ],
        ];

        yield 'False configuration' => [
            'config' => [
                'https_only' => false,
            ],
            'expected' => [
                'https_only' => false,
            ],
        ];

        yield 'Null configuration' => [
            'config' => [
                'https_only' => null,
            ],
            'expected' => [
                'https_only' => true,
            ],
        ];
    }

    protected function setUp(): void
    {
        $this->processor = new Processor();
    }
}
