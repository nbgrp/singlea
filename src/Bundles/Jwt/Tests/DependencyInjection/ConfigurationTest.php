<?php declare(strict_types=1);
// SPDX-License-Identifier: BSD-3-Clause

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
     * @dataProvider validConfigurationProvider
     */
    public function testValidConfiguration(array $config, array $expected): void
    {
        self::assertSame($expected, $this->processor->processConfiguration(new Configuration(), [$config]));
    }

    public function validConfigurationProvider(): \Generator
    {
        yield 'Default configuration' => [
            'config' => [],
            'expected' => [
                'default_token_ttl' => 600,
                'jwt_issuer' => null,
            ],
        ];

        yield 'Custom configuration' => [
            'config' => [
                'default_token_ttl' => 3600,
                'jwt_issuer' => 'test-app',
            ],
            'expected' => [
                'default_token_ttl' => 3600,
                'jwt_issuer' => 'test-app',
            ],
        ];

        yield 'Zero default token ttl' => [
            'config' => [
                'default_token_ttl' => 0,
            ],
            'expected' => [
                'default_token_ttl' => 0,
                'jwt_issuer' => null,
            ],
        ];
    }

    public function testInvalidConfiguration(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('Invalid configuration for path "singlea_jwt.default_token_ttl": Default token TTL should be a positive number or zero.');

        $this->processor->processConfiguration(new Configuration(), [[
            'default_token_ttl' => -600,
        ]]);
    }

    protected function setUp(): void
    {
        $this->processor = new Processor();
    }
}
