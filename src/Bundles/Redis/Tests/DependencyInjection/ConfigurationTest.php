<?php declare(strict_types=1);
// SPDX-License-Identifier: BSD-3-Clause

namespace SingleA\Bundles\Redis\Tests\DependencyInjection;

use PHPUnit\Framework\TestCase;
use SingleA\Bundles\Redis\DependencyInjection\Configuration;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;

/**
 * @covers \SingleA\Bundles\Redis\DependencyInjection\Configuration
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
                'client_last_access_key' => 'singlea:last-access',
                'snc_redis_client' => 'default',
                'config_managers' => [],
            ],
        ];

        yield 'Custom configuration' => [
            'config' => [
                'client_last_access_key' => 'singlea:custom-last-access',
                'snc_redis_client' => 'custom',
                'config_managers' => [
                    'signature' => [
                        'key' => 'signature',
                        'config_marshaller' => 'singlea.signature_marshaller',
                        'required' => true,
                    ],
                    'tokenizer' => [
                        'key' => 'tokenizer',
                        'config_marshaller' => 'singlea.tokenizer_marshaller',
                    ],
                ],
            ],
            'expected' => [
                'client_last_access_key' => 'singlea:custom-last-access',
                'snc_redis_client' => 'custom',
                'config_managers' => [
                    'signature' => [
                        'key' => 'signature',
                        'config_marshaller' => 'singlea.signature_marshaller',
                        'required' => true,
                    ],
                    'tokenizer' => [
                        'key' => 'tokenizer',
                        'config_marshaller' => 'singlea.tokenizer_marshaller',
                        'required' => false,
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider invalidConfigurationProvider
     */
    public function testInvalidConfiguration(array $config, string $expectedMessage): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage($expectedMessage);

        $this->processor->processConfiguration(new Configuration(), [$config]);
    }

    public function invalidConfigurationProvider(): \Generator
    {
        yield 'Empty client key' => [
            'config' => [
                'client_last_access_key' => '',
            ],
            'expectedMessage' => 'The path "singlea_redis.client_last_access_key" cannot contain an empty value, but got "".',
        ];

        yield 'Invalid SncRedis client name' => [
            'config' => [
                'snc_redis_client' => 0,
            ],
            'expectedMessage' => 'Invalid configuration for path "singlea_redis.snc_redis_client": SncRedis client name must be a string.',
        ];

        yield 'Empty config manager key' => [
            'config' => [
                'config_managers' => [
                    'signature' => [
                        'key' => '',
                        'config_marshaller' => 'singlea.signature_marshaller',
                    ],
                ],
            ],
            'expectedMessage' => 'The path "singlea_redis.config_managers.signature.key" cannot contain an empty value, but got "".',
        ];

        yield 'Empty config manager config marshaller' => [
            'config' => [
                'config_managers' => [
                    'signature' => [
                        'key' => 'key',
                        'config_marshaller' => '',
                    ],
                ],
            ],
            'expectedMessage' => 'The path "singlea_redis.config_managers.signature.config_marshaller" cannot contain an empty value, but got "".',
        ];
    }

    protected function setUp(): void
    {
        $this->processor = new Processor();
    }
}
