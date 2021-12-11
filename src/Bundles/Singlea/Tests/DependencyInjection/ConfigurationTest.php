<?php declare(strict_types=1);
// SPDX-License-Identifier: BSD-3-Clause

namespace SingleA\Bundles\Singlea\Tests\DependencyInjection;

use PHPUnit\Framework\TestCase;
use SingleA\Bundles\Singlea\DependencyInjection\Configuration;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;

/**
 * @covers \SingleA\Bundles\Singlea\DependencyInjection\Configuration
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
            'config' => [
                'ticket' => ['domain' => 'example.test'],
            ],
            'expected' => [
                'ticket' => [
                    'domain' => 'example.test',
                    'header' => 'X-Ticket',
                    'cookie_name' => 'tkt',
                    'ttl' => 3600,
                    'samesite' => 'lax',
                ],
                'client' => [
                    'id_query_parameter' => 'client_id',
                    'secret_query_parameter' => 'secret',
                    'trusted_clients' => null,
                    'trusted_registrars' => null,
                    'registration_ticket_header' => 'X-Registration-Ticket',
                ],
                'authentication' => [
                    'sticky_session' => false,
                    'redirect_uri_query_parameter' => 'redirect_uri',
                ],
                'signature' => [
                    'request_ttl' => 60,
                    'signature_query_parameter' => 'sg',
                    'timestamp_query_parameter' => 'ts',
                    'extra_exclude_query_parameters' => [],
                ],
                'encryption' => [
                    'client_keys' => [],
                    'user_keys' => [],
                ],
                'realm' => [
                    'default' => 'main',
                    'query_parameter' => 'realm',
                ],
                'marshaller' => [
                    'use_igbinary' => null,
                ],
                'user_attributes' => [
                    'use_igbinary' => null,
                ],
            ],
        ];

        yield 'Custom configuration' => [
            'config' => [
                'client' => [
                    'id_query_parameter' => 'cid',
                    'secret_query_parameter' => 'sct',
                    'trusted_clients' => 'REMOTE_ADDR',
                    'trusted_registrars' => '192.168.0.1/16',
                    'registration_ticket_header' => 'X-Reg-Ticket',
                ],
                'ticket' => [
                    'header' => 'X-Tkt',
                    'cookie_name' => 'tct',
                    'ttl' => 600,
                    'domain' => '.singlea.test',
                    'samesite' => 'none',
                ],
                'authentication' => [
                    'sticky_session' => true,
                    'redirect_uri_query_parameter' => 'ru',
                ],
                'signature' => [
                    'request_ttl' => 30,
                    'signature_query_parameter' => 'sig',
                    'timestamp_query_parameter' => 'tsp',
                    'extra_exclude_query_parameters' => ['utm', 'tag'],
                ],
                'encryption' => [
                    'client_keys' => ['ckey1', 'ckey2'],
                    'user_keys' => ['ukey1'],
                ],
                'realm' => [
                    'default' => 'test',
                    'query_parameter' => 'r',
                ],
                'marshaller' => [
                    'use_igbinary' => true,
                ],
                'user_attributes' => [
                    'use_igbinary' => false,
                ],
            ],
            'expected' => [
                'client' => [
                    'id_query_parameter' => 'cid',
                    'secret_query_parameter' => 'sct',
                    'trusted_clients' => 'REMOTE_ADDR',
                    'trusted_registrars' => '192.168.0.1/16',
                    'registration_ticket_header' => 'X-Reg-Ticket',
                ],
                'ticket' => [
                    'header' => 'X-Tkt',
                    'cookie_name' => 'tct',
                    'ttl' => 600,
                    'domain' => '.singlea.test',
                    'samesite' => 'none',
                ],
                'authentication' => [
                    'sticky_session' => true,
                    'redirect_uri_query_parameter' => 'ru',
                ],
                'signature' => [
                    'request_ttl' => 30,
                    'signature_query_parameter' => 'sig',
                    'timestamp_query_parameter' => 'tsp',
                    'extra_exclude_query_parameters' => ['utm', 'tag'],
                ],
                'encryption' => [
                    'client_keys' => ['ckey1', 'ckey2'],
                    'user_keys' => ['ukey1'],
                ],
                'realm' => [
                    'default' => 'test',
                    'query_parameter' => 'r',
                ],
                'marshaller' => [
                    'use_igbinary' => true,
                ],
                'user_attributes' => [
                    'use_igbinary' => false,
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
        yield 'Empty "client.id_query_parameter"' => [
            'config' => [
                'client' => [
                    'id_query_parameter' => '',
                ],
                'ticket' => ['domain' => 'example.test'],
            ],
            'expectedMessage' => 'The path "singlea.client.id_query_parameter" cannot contain an empty value, but got "".',
        ];

        yield 'Empty "client.secret_query_parameter"' => [
            'config' => [
                'client' => [
                    'secret_query_parameter' => '',
                ],
                'ticket' => ['domain' => 'example.test'],
            ],
            'expectedMessage' => 'The path "singlea.client.secret_query_parameter" cannot contain an empty value, but got "".',
        ];

        yield 'Empty "client.registration_ticket_header"' => [
            'config' => [
                'client' => [
                    'registration_ticket_header' => '',
                ],
                'ticket' => ['domain' => 'example.test'],
            ],
            'expectedMessage' => 'The path "singlea.client.registration_ticket_header" cannot contain an empty value, but got "".',
        ];

        yield 'Empty "ticket.header"' => [
            'config' => [
                'ticket' => [
                    'header' => '',
                    'domain' => 'example.test',
                ],
            ],
            'expectedMessage' => 'The path "singlea.ticket.header" cannot contain an empty value, but got "".',
        ];

        yield 'Empty "ticket.cookie_name"' => [
            'config' => [
                'ticket' => [
                    'cookie_name' => '',
                    'domain' => 'example.test',
                ],
            ],
            'expectedMessage' => 'The path "singlea.ticket.cookie_name" cannot contain an empty value, but got "".',
        ];

        yield 'Empty "ticket.domain"' => [
            'config' => [
                'ticket' => [
                    'domain' => '',
                ],
            ],
            'expectedMessage' => 'The path "singlea.ticket.domain" cannot contain an empty value, but got "".',
        ];

        yield 'Empty "ticket.samesite"' => [
            'config' => [
                'ticket' => [
                    'domain' => 'example.test',
                    'samesite' => '',
                ],
            ],
            'expectedMessage' => 'The value "" is not allowed for path "singlea.ticket.samesite". Permissible values: "lax", "strict", "none"',
        ];

        yield 'Unknown "ticket.samesite"' => [
            'config' => [
                'ticket' => [
                    'domain' => 'example.test',
                    'samesite' => 'unknown',
                ],
            ],
            'expectedMessage' => 'The value "unknown" is not allowed for path "singlea.ticket.samesite". Permissible values: "lax", "strict", "none"',
        ];

        yield 'Empty "authentication.redirect_uri_query_parameter"' => [
            'config' => [
                'authentication' => [
                    'redirect_uri_query_parameter' => '',
                ],
                'ticket' => ['domain' => 'example.test'],
            ],
            'expectedMessage' => 'The path "singlea.authentication.redirect_uri_query_parameter" cannot contain an empty value, but got "".',
        ];

        yield 'Empty "signature.signature_query_parameter"' => [
            'config' => [
                'signature' => [
                    'signature_query_parameter' => '',
                ],
                'ticket' => ['domain' => 'example.test'],
            ],
            'expectedMessage' => 'The path "singlea.signature.signature_query_parameter" cannot contain an empty value, but got "".',
        ];

        yield 'Empty "signature.timestamp_query_parameter"' => [
            'config' => [
                'signature' => [
                    'timestamp_query_parameter' => '',
                ],
                'ticket' => ['domain' => 'example.test'],
            ],
            'expectedMessage' => 'The path "singlea.signature.timestamp_query_parameter" cannot contain an empty value, but got "".',
        ];

        yield 'Empty "realm.default"' => [
            'config' => [
                'realm' => [
                    'default' => '',
                ],
                'ticket' => ['domain' => 'example.test'],
            ],
            'expectedMessage' => 'The path "singlea.realm.default" cannot contain an empty value, but got "".',
        ];

        yield 'Empty "realm.query_parameter"' => [
            'config' => [
                'realm' => [
                    'query_parameter' => '',
                ],
                'ticket' => ['domain' => 'example.test'],
            ],
            'expectedMessage' => 'The path "singlea.realm.query_parameter" cannot contain an empty value, but got "".',
        ];

        yield 'No "ticket"' => [
            'config' => [],
            'expectedMessage' => 'The child config "ticket" under "singlea" must be configured.',
        ];

        yield 'No "ticket.domain"' => [
            'config' => [
                'ticket' => [],
            ],
            'expectedMessage' => 'The child config "domain" under "singlea.ticket" must be configured: The user ticket cookie domain name.',
        ];
    }

    protected function setUp(): void
    {
        $this->processor = new Processor();
    }
}
