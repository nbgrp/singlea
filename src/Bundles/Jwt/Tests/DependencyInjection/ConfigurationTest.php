<?php
// SPDX-License-Identifier: BSD-3-Clause

declare(strict_types=1);

namespace SingleA\Bundles\Jwt\Tests\DependencyInjection;

use PHPUnit\Framework\TestCase;
use SingleA\Bundles\Jwt\DependencyInjection\Configuration;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;

/**
 * @covers \SingleA\Bundles\Jwt\DependencyInjection\Configuration
 *
 * @internal
 */
final class ConfigurationTest extends TestCase
{
    private Processor $processor;

    /**
     * @dataProvider provideValidConfigurationCases
     */
    public function testValidConfiguration(array $config, array $expected): void
    {
        self::assertSame($expected, $this->processor->processConfiguration(new Configuration(), [$config]));
    }

    public function provideValidConfigurationCases(): iterable
    {
        yield 'Default configuration' => [
            'config' => [
                'issuer' => 'https://sso.domain.org/',
            ],
            'expected' => [
                'issuer' => 'https://sso.domain.org/',
                'config_default_ttl' => 600,
            ],
        ];

        yield 'Custom configuration' => [
            'config' => [
                'config_default_ttl' => 3600,
                'issuer' => 'https://sso.example.com/',
            ],
            'expected' => [
                'config_default_ttl' => 3600,
                'issuer' => 'https://sso.example.com/',
            ],
        ];

        yield 'Zero default token ttl' => [
            'config' => [
                'config_default_ttl' => 0,
                'issuer' => 'https://sso.domain.org/',
            ],
            'expected' => [
                'config_default_ttl' => 0,
                'issuer' => 'https://sso.domain.org/',
            ],
        ];
    }

    /**
     * @dataProvider provideInvalidConfigurationCases
     */
    public function testInvalidConfiguration(array $config, string $expectedMessage): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage($expectedMessage);

        $this->processor->processConfiguration(new Configuration(), [$config]);
    }

    public function provideInvalidConfigurationCases(): iterable
    {
        yield 'Invalid config_default_ttl' => [
            'config' => [
                'config_default_ttl' => -600,
                'issuer' => 'https://sso.domain.org/',
            ],
            'expectedMessage' => 'Invalid configuration for path "singlea_jwt.config_default_ttl": Default token TTL should be a positive number or zero.',
        ];

        yield 'No issuer' => [
            'config' => [],
            'expectedMessage' => 'The child config "issuer" under "singlea_jwt" must be configured.',
        ];
    }

    protected function setUp(): void
    {
        $this->processor = new Processor();
    }
}
