<?php declare(strict_types=1);
// SPDX-License-Identifier: BSD-3-Clause

namespace SingleA\Bundles\JwtFetcher\Tests\FeatureConfig;

use Jose\Component\Core\AlgorithmManagerFactory;
use Jose\Component\Core\JWK;
use Jose\Component\Encryption;
use Jose\Component\Signature;
use PHPUnit\Framework\TestCase;
use SingleA\Bundles\JwtFetcher\FeatureConfig\JwtFetcherConfigFactory;

/**
 * @covers \SingleA\Bundles\JwtFetcher\FeatureConfig\JwtFetcherConfigFactory
 *
 * @internal
 */
final class JwtFetcherConfigFactoryTest extends TestCase
{
    private static AlgorithmManagerFactory $algorithmManagerFactory;
    private static Encryption\Compression\CompressionMethodManagerFactory $compressionMethodManagerFactory;

    public static function setUpBeforeClass(): void
    {
        self::$algorithmManagerFactory = new AlgorithmManagerFactory();
        self::$algorithmManagerFactory->add('ES256', new Signature\Algorithm\ES256());
        self::$algorithmManagerFactory->add('ES384', new Signature\Algorithm\ES384());
        self::$algorithmManagerFactory->add('ES512', new Signature\Algorithm\ES512());
        self::$algorithmManagerFactory->add('ES256K', new Signature\Algorithm\ES256K());
        self::$algorithmManagerFactory->add('EdDSA', new Signature\Algorithm\EdDSA());
        self::$algorithmManagerFactory->add('RS256', new Signature\Algorithm\RS256());
        self::$algorithmManagerFactory->add('RS384', new Signature\Algorithm\RS384());
        self::$algorithmManagerFactory->add('RS512', new Signature\Algorithm\RS512());
        self::$algorithmManagerFactory->add('PS256', new Signature\Algorithm\PS256());
        self::$algorithmManagerFactory->add('PS384', new Signature\Algorithm\PS384());
        self::$algorithmManagerFactory->add('PS512', new Signature\Algorithm\PS512());
        self::$algorithmManagerFactory->add('HS256', new Signature\Algorithm\HS256());
        self::$algorithmManagerFactory->add('HS384', new Signature\Algorithm\HS384());
        self::$algorithmManagerFactory->add('HS512', new Signature\Algorithm\HS512());
        self::$algorithmManagerFactory->add('ECDH-ES', new Encryption\Algorithm\KeyEncryption\ECDHES());
        self::$algorithmManagerFactory->add('ECDH-ES+A128KW', new Encryption\Algorithm\KeyEncryption\ECDHESA128KW());
        self::$algorithmManagerFactory->add('ECDH-ES+A192KW', new Encryption\Algorithm\KeyEncryption\ECDHESA192KW());
        self::$algorithmManagerFactory->add('ECDH-ES+A256KW', new Encryption\Algorithm\KeyEncryption\ECDHESA256KW());
        self::$algorithmManagerFactory->add('RSA1_5', new Encryption\Algorithm\KeyEncryption\RSA15());
        self::$algorithmManagerFactory->add('RSA-OAEP', new Encryption\Algorithm\KeyEncryption\RSAOAEP());
        self::$algorithmManagerFactory->add('RSA-OAEP-256', new Encryption\Algorithm\KeyEncryption\RSAOAEP256());
        self::$algorithmManagerFactory->add('RSA-OAEP-384', new Encryption\Algorithm\KeyEncryption\RSAOAEP384());
        self::$algorithmManagerFactory->add('RSA-OAEP-512', new Encryption\Algorithm\KeyEncryption\RSAOAEP512());
        self::$algorithmManagerFactory->add('A128KW', new Encryption\Algorithm\KeyEncryption\A128KW());
        self::$algorithmManagerFactory->add('A192KW', new Encryption\Algorithm\KeyEncryption\A192KW());
        self::$algorithmManagerFactory->add('A256KW', new Encryption\Algorithm\KeyEncryption\A256KW());
        self::$algorithmManagerFactory->add('A128GCMKW', new Encryption\Algorithm\KeyEncryption\A128GCMKW());
        self::$algorithmManagerFactory->add('A192GCMKW', new Encryption\Algorithm\KeyEncryption\A192GCMKW());
        self::$algorithmManagerFactory->add('A256GCMKW', new Encryption\Algorithm\KeyEncryption\A256GCMKW());
        self::$algorithmManagerFactory->add('A128CTR', new Encryption\Algorithm\KeyEncryption\A128CTR());
        self::$algorithmManagerFactory->add('A192CTR', new Encryption\Algorithm\KeyEncryption\A192CTR());
        self::$algorithmManagerFactory->add('A256CTR', new Encryption\Algorithm\KeyEncryption\A256CTR());
        self::$algorithmManagerFactory->add('chacha20-poly1305', new Encryption\Algorithm\KeyEncryption\Chacha20Poly1305());
        self::$algorithmManagerFactory->add('A128GCM', new Encryption\Algorithm\ContentEncryption\A128GCM());
        self::$algorithmManagerFactory->add('A192GCM', new Encryption\Algorithm\ContentEncryption\A192GCM());
        self::$algorithmManagerFactory->add('A256GCM', new Encryption\Algorithm\ContentEncryption\A256GCM());
        self::$algorithmManagerFactory->add('A128CBC-HS256', new Encryption\Algorithm\ContentEncryption\A128CBCHS256());
        self::$algorithmManagerFactory->add('A192CBC-HS384', new Encryption\Algorithm\ContentEncryption\A192CBCHS384());
        self::$algorithmManagerFactory->add('A256CBC-HS512', new Encryption\Algorithm\ContentEncryption\A256CBCHS512());
        self::$algorithmManagerFactory->add('A128CCM-16-64', new Encryption\Algorithm\ContentEncryption\A128CCM_16_64());
        self::$algorithmManagerFactory->add('A128CCM-16-128', new Encryption\Algorithm\ContentEncryption\A128CCM_16_128());
        self::$algorithmManagerFactory->add('A128CCM-64-64', new Encryption\Algorithm\ContentEncryption\A128CCM_64_64());
        self::$algorithmManagerFactory->add('A128CCM-64-128', new Encryption\Algorithm\ContentEncryption\A128CCM_64_128());
        self::$algorithmManagerFactory->add('A256CCM-16-64', new Encryption\Algorithm\ContentEncryption\A256CCM_16_64());
        self::$algorithmManagerFactory->add('A256CCM-16-128', new Encryption\Algorithm\ContentEncryption\A256CCM_16_128());
        self::$algorithmManagerFactory->add('A256CCM-64-64', new Encryption\Algorithm\ContentEncryption\A256CCM_64_64());
        self::$algorithmManagerFactory->add('A256CCM-64-128', new Encryption\Algorithm\ContentEncryption\A256CCM_64_128());

        self::$compressionMethodManagerFactory = new Encryption\Compression\CompressionMethodManagerFactory();
        self::$compressionMethodManagerFactory->add('DEF', new Encryption\Compression\Deflate());
    }

    public function testStrings(): void
    {
        $factory = new JwtFetcherConfigFactory(true, self::$algorithmManagerFactory, self::$compressionMethodManagerFactory);

        self::assertSame('SingleA\Bundles\JwtFetcher\FeatureConfig\JwtFetcherConfig', $factory->getConfigClass());
        self::assertSame('payload', $factory->getKey());
        self::assertSame('jwt', $factory->getHash());
    }

    /**
     * @dataProvider successfulCreateProvider
     */
    public function testSuccessfulCreate(
        bool $httpsOnly,
        array $input,
        string $expectedEndpoint,
        ?array $expectedClaims,
        string $expectedRequestJwsAlgorithm,
        array $expectedRequestJwsJwkKeys,
        ?int $expectedRequestJwsJwkLength,
        ?string $expectedRequestJwsJwkLengthKey,
        string $expectedSerializedRequestJweConfig,
        ?array $expectedRequestOptions,
        string $expectedSerializedResponseJwsConfig,
        ?string $expectedResponseJweKeyAlgorithm,
        ?string $expectedResponseJweContentAlgorithm,
        ?string $expectedResponseJweCompression,
        ?array $expectedResponseJweRecipientJwkKeys,
        ?int $expectedResponseJweRecipientJwkLength,
        ?string $expectedResponseJweRecipientJwkLengthKey,
        array $expectedOutputRequestJwkKeys,
        ?array $expectedOutputResponseRecipientJwkKeys,
    ): void {
        $output = null;
        $factory = new JwtFetcherConfigFactory($httpsOnly, self::$algorithmManagerFactory, self::$compressionMethodManagerFactory);
        $config = $factory->create($input, $output);

        self::assertSame($expectedEndpoint, $config->getEndpoint());
        self::assertSame($expectedClaims, $config->getClaims());
        self::assertSame($expectedRequestOptions, $config->getRequestOptions());

        $requestJwsConfig = $config->getRequestJwsConfig();
        self::assertSame($expectedRequestJwsAlgorithm, $requestJwsConfig->getAlgorithm());

        $requestJwsJwkValues = $requestJwsConfig->getJwk()->all();
        foreach ($expectedRequestJwsJwkKeys as $key) {
            self::assertArrayHasKey($key, $requestJwsJwkValues);
        }
        if ($expectedRequestJwsJwkLength && $expectedRequestJwsJwkLengthKey) {
            self::assertSame($expectedRequestJwsJwkLength, \strlen($requestJwsJwkValues[$expectedRequestJwsJwkLengthKey]));
        }

        self::assertSame($expectedSerializedRequestJweConfig, base64_encode(serialize($config->getRequestJweConfig())));
        self::assertSame($expectedSerializedResponseJwsConfig, base64_encode(serialize($config->getResponseJwsConfig())));

        $responseJweConfig = $config->getResponseJweConfig();
        if ($responseJweConfig) {
            self::assertSame($expectedResponseJweKeyAlgorithm, $responseJweConfig->getKeyAlgorithm());
            self::assertSame($expectedResponseJweContentAlgorithm, $responseJweConfig->getContentAlgorithm());
            self::assertSame($expectedResponseJweCompression, $responseJweConfig->getCompression());

            $responseJwsRecipientJwkValues = $responseJweConfig->getRecipientJwk()->all();
            foreach ($expectedResponseJweRecipientJwkKeys as $key) {
                self::assertArrayHasKey($key, $responseJwsRecipientJwkValues);
            }
            if ($expectedResponseJweRecipientJwkLength && $expectedResponseJweRecipientJwkLengthKey) {
                self::assertSame($expectedResponseJweRecipientJwkLength, \strlen($responseJwsRecipientJwkValues[$expectedResponseJweRecipientJwkLengthKey]));
            }
        }

        self::assertIsArray($output);
        self::assertArrayHasKey('request', $output);
        self::assertArrayHasKey('jwk', $output['request']);
        self::assertInstanceOf(JWK::class, $output['request']['jwk']);
        $requestJwkValues = $output['request']['jwk']->all();
        foreach ($expectedOutputRequestJwkKeys as $key) {
            self::assertArrayHasKey($key, $requestJwkValues);
        }

        if (!$expectedOutputResponseRecipientJwkKeys) {
            return;
        }

        self::assertArrayHasKey('response', $output);
        self::assertArrayHasKey('jwk', $output['response']);
        self::assertInstanceOf(JWK::class, $output['response']['jwk']);
        $responseJwkValues = $output['response']['jwk']->all();
        foreach ($expectedOutputResponseRecipientJwkKeys as $key) {
            self::assertArrayHasKey($key, $responseJwkValues);
        }
    }

    public function successfulCreateProvider(): \Generator
    {
        yield 'HTTP endpoint, request: JWS with ES256 alg, JWE (no zip), options; response: JWS with HS256, no JWE' => [
            'httpsOnly' => false,
            'input' => [
                'endpoint' => 'http://endpoint.test',
                'claims' => ['username', 'email'],
                'request' => [
                    'jws' => [
                        'alg' => 'ES256',
                    ],
                    'jwe' => [
                        'alg' => 'ECDH-ES',
                        'enc' => 'A128GCM',
                        'jwk' => [
                            'kty' => 'EC',
                            'crv' => '...',
                        ],
                    ],
                    'options' => ['timeout' => 10],
                ],
                'response' => [
                    'jws' => [
                        'alg' => 'HS256',
                        'jwk' => [
                            'kty' => 'oct',
                            'k' => '...',
                        ],
                    ],
                ],
            ],
            'expectedEndpoint' => 'http://endpoint.test',
            'expectedClaims' => ['username', 'email'],
            'expectedRequestJwsAlgorithm' => 'ES256',
            'expectedRequestJwsJwkKeys' => ['alg', 'use', 'kty', 'crv', 'd', 'x', 'y'],
            'expectedRequestJwsJwkLength' => null,
            'expectedRequestJwsJwkLengthKey' => null,
            'expectedSerializedRequestJweConfig' => 'Tzo1MDoiU2luZ2xlQVxCdW5kbGVzXEp3dEZldGNoZXJcRmVhdHVyZUNvbmZpZ1xKd2VDb25maWciOjQ6e3M6MToiYSI7czo3OiJFQ0RILUVTIjtzOjE6ImMiO3M6NzoiQTEyOEdDTSI7czoxOiJ6IjtOO3M6MToiayI7TzoyMzoiSm9zZVxDb21wb25lbnRcQ29yZVxKV0siOjE6e3M6MzE6IgBKb3NlXENvbXBvbmVudFxDb3JlXEpXSwB2YWx1ZXMiO2E6Mjp7czozOiJrdHkiO3M6MjoiRUMiO3M6MzoiY3J2IjtzOjM6Ii4uLiI7fX19',
            'expectedRequestOptions' => ['timeout' => 10],
            'expectedSerializedResponseJwsConfig' => 'Tzo1MDoiU2luZ2xlQVxCdW5kbGVzXEp3dEZldGNoZXJcRmVhdHVyZUNvbmZpZ1xKd3NDb25maWciOjI6e3M6MToiYSI7czo1OiJIUzI1NiI7czoxOiJrIjtPOjIzOiJKb3NlXENvbXBvbmVudFxDb3JlXEpXSyI6MTp7czozMToiAEpvc2VcQ29tcG9uZW50XENvcmVcSldLAHZhbHVlcyI7YToyOntzOjM6Imt0eSI7czozOiJvY3QiO3M6MToiayI7czozOiIuLi4iO319fQ==',
            'expectedResponseJweKeyAlgorithm' => null,
            'expectedResponseJweContentAlgorithm' => null,
            'expectedResponseJweCompression' => null,
            'expectedResponseJweRecipientJwkKeys' => null,
            'expectedResponseJweRecipientJwkLength' => null,
            'expectedResponseJweRecipientJwkLengthKey' => null,
            'expectedOutputRequestJwkKeys' => ['alg', 'use', 'kty', 'crv', 'x', 'y'],
            'expectedOutputResponseRecipientJwkKeys' => null,
        ];

        yield 'No claims, request: JWS with RS256 alg + 1024 bits, JWE, no options; response: JWS with ES256, JWE (no zip)' => [
            'httpsOnly' => true,
            'input' => [
                'endpoint' => 'https://endpoint.test',
                'request' => [
                    'jws' => [
                        'alg' => 'RS256',
                        'bits' => 1024,
                    ],
                    'jwe' => [
                        'alg' => 'RSA1_5',
                        'enc' => 'A128CBC-HS256',
                        'zip' => 'DEF',
                        'jwk' => [
                            'kty' => 'RSA',
                            'n' => '...',
                        ],
                    ],
                ],
                'response' => [
                    'jws' => [
                        'alg' => 'ES384',
                        'jwk' => [
                            'kty' => 'EC',
                            'crv' => '...',
                        ],
                    ],
                    'jwe' => [
                        'alg' => 'A128KW',
                        'enc' => 'A128CCM-16-64',
                        'zip' => 'DEF',
                    ],
                ],
            ],
            'expectedEndpoint' => 'https://endpoint.test',
            'expectedClaims' => null,
            'expectedRequestJwsAlgorithm' => 'RS256',
            'expectedRequestJwsJwkKeys' => ['alg', 'use', 'kty', 'n', 'e', 'd', 'p', 'q', 'dp', 'dq', 'qi'],
            'expectedRequestJwsJwkLength' => 171,
            'expectedRequestJwsJwkLengthKey' => 'n',
            'expectedSerializedRequestJweConfig' => 'Tzo1MDoiU2luZ2xlQVxCdW5kbGVzXEp3dEZldGNoZXJcRmVhdHVyZUNvbmZpZ1xKd2VDb25maWciOjQ6e3M6MToiYSI7czo2OiJSU0ExXzUiO3M6MToiYyI7czoxMzoiQTEyOENCQy1IUzI1NiI7czoxOiJ6IjtzOjM6IkRFRiI7czoxOiJrIjtPOjIzOiJKb3NlXENvbXBvbmVudFxDb3JlXEpXSyI6MTp7czozMToiAEpvc2VcQ29tcG9uZW50XENvcmVcSldLAHZhbHVlcyI7YToyOntzOjM6Imt0eSI7czozOiJSU0EiO3M6MToibiI7czozOiIuLi4iO319fQ==',
            'expectedRequestOptions' => null,
            'expectedSerializedResponseJwsConfig' => 'Tzo1MDoiU2luZ2xlQVxCdW5kbGVzXEp3dEZldGNoZXJcRmVhdHVyZUNvbmZpZ1xKd3NDb25maWciOjI6e3M6MToiYSI7czo1OiJFUzM4NCI7czoxOiJrIjtPOjIzOiJKb3NlXENvbXBvbmVudFxDb3JlXEpXSyI6MTp7czozMToiAEpvc2VcQ29tcG9uZW50XENvcmVcSldLAHZhbHVlcyI7YToyOntzOjM6Imt0eSI7czoyOiJFQyI7czozOiJjcnYiO3M6MzoiLi4uIjt9fX0=',
            'expectedResponseJweKeyAlgorithm' => 'A128KW',
            'expectedResponseJweContentAlgorithm' => 'A128CCM-16-64',
            'expectedResponseJweCompression' => 'DEF',
            'expectedResponseJweRecipientJwkKeys' => ['alg', 'use', 'kty', 'k'],
            'expectedResponseJweRecipientJwkLength' => 43,
            'expectedResponseJweRecipientJwkLengthKey' => 'k',
            'expectedOutputRequestJwkKeys' => ['alg', 'use', 'kty', 'n', 'e'],
            'expectedOutputResponseRecipientJwkKeys' => ['alg', 'use', 'kty', 'k'],
        ];

        yield 'No claims, request: JWS with HS384 alg + 512 bits, no JWE, no options; response: JWS with HS256, JWE 512 bits' => [
            'httpsOnly' => true,
            'input' => [
                'endpoint' => 'https://endpoint.test',
                'request' => [
                    'jws' => [
                        'alg' => 'HS384',
                        'bits' => 512,
                    ],
                ],
                'response' => [
                    'jws' => [
                        'alg' => 'ES256K',
                        'jwk' => [
                            'kty' => 'EC',
                            'crv' => '...',
                        ],
                    ],
                    'jwe' => [
                        'alg' => 'A128GCMKW',
                        'bits' => 512,
                        'enc' => 'A256CCM-16-64',
                        'zip' => 'DEF',
                    ],
                ],
            ],
            'expectedEndpoint' => 'https://endpoint.test',
            'expectedClaims' => null,
            'expectedRequestJwsAlgorithm' => 'HS384',
            'expectedRequestJwsJwkKeys' => ['alg', 'use', 'kty', 'k'],
            'expectedRequestJwsJwkLength' => 86,
            'expectedRequestJwsJwkLengthKey' => 'k',
            'expectedSerializedRequestJweConfig' => 'Tjs=',
            'expectedRequestOptions' => null,
            'expectedSerializedResponseJwsConfig' => 'Tzo1MDoiU2luZ2xlQVxCdW5kbGVzXEp3dEZldGNoZXJcRmVhdHVyZUNvbmZpZ1xKd3NDb25maWciOjI6e3M6MToiYSI7czo2OiJFUzI1NksiO3M6MToiayI7TzoyMzoiSm9zZVxDb21wb25lbnRcQ29yZVxKV0siOjE6e3M6MzE6IgBKb3NlXENvbXBvbmVudFxDb3JlXEpXSwB2YWx1ZXMiO2E6Mjp7czozOiJrdHkiO3M6MjoiRUMiO3M6MzoiY3J2IjtzOjM6Ii4uLiI7fX19',
            'expectedResponseJweKeyAlgorithm' => 'A128GCMKW',
            'expectedResponseJweContentAlgorithm' => 'A256CCM-16-64',
            'expectedResponseJweCompression' => 'DEF',
            'expectedResponseJweRecipientJwkKeys' => ['alg', 'use', 'kty', 'k'],
            'expectedResponseJweRecipientJwkLength' => 86,
            'expectedResponseJweRecipientJwkLengthKey' => 'k',
            'expectedOutputRequestJwkKeys' => ['alg', 'use', 'kty', 'k'],
            'expectedOutputResponseRecipientJwkKeys' => ['alg', 'use', 'kty', 'k'],
        ];

        yield 'No claims, request: JWS with EdDSA alg, JWE, no options; response: JWS with HS256, JWE RSA alg + 2048 bits' => [
            'httpsOnly' => true,
            'input' => [
                'endpoint' => 'https://endpoint.test',
                'request' => [
                    'jws' => [
                        'alg' => 'EdDSA',
                    ],
                    'jwe' => [
                        'alg' => 'ECDH-ES+A128KW',
                        'enc' => 'A256GCM',
                        'jwk' => [
                            'kty' => 'RSA',
                            'n' => '...',
                        ],
                    ],
                ],
                'response' => [
                    'jws' => [
                        'alg' => 'RS384',
                        'jwk' => [
                            'kty' => 'RSA',
                            'n' => '...',
                        ],
                    ],
                    'jwe' => [
                        'alg' => 'RSA-OAEP',
                        'bits' => 2048,
                        'enc' => 'A192GCM',
                        'zip' => 'DEF',
                    ],
                ],
            ],
            'expectedEndpoint' => 'https://endpoint.test',
            'expectedClaims' => null,
            'expectedRequestJwsAlgorithm' => 'EdDSA',
            'expectedRequestJwsJwkKeys' => ['alg', 'use', 'kty', 'crv', 'd', 'x'],
            'expectedRequestJwsJwkLength' => 43,
            'expectedRequestJwsJwkLengthKey' => 'd',
            'expectedSerializedRequestJweConfig' => 'Tzo1MDoiU2luZ2xlQVxCdW5kbGVzXEp3dEZldGNoZXJcRmVhdHVyZUNvbmZpZ1xKd2VDb25maWciOjQ6e3M6MToiYSI7czoxNDoiRUNESC1FUytBMTI4S1ciO3M6MToiYyI7czo3OiJBMjU2R0NNIjtzOjE6InoiO047czoxOiJrIjtPOjIzOiJKb3NlXENvbXBvbmVudFxDb3JlXEpXSyI6MTp7czozMToiAEpvc2VcQ29tcG9uZW50XENvcmVcSldLAHZhbHVlcyI7YToyOntzOjM6Imt0eSI7czozOiJSU0EiO3M6MToibiI7czozOiIuLi4iO319fQ==',
            'expectedRequestOptions' => null,
            'expectedSerializedResponseJwsConfig' => 'Tzo1MDoiU2luZ2xlQVxCdW5kbGVzXEp3dEZldGNoZXJcRmVhdHVyZUNvbmZpZ1xKd3NDb25maWciOjI6e3M6MToiYSI7czo1OiJSUzM4NCI7czoxOiJrIjtPOjIzOiJKb3NlXENvbXBvbmVudFxDb3JlXEpXSyI6MTp7czozMToiAEpvc2VcQ29tcG9uZW50XENvcmVcSldLAHZhbHVlcyI7YToyOntzOjM6Imt0eSI7czozOiJSU0EiO3M6MToibiI7czozOiIuLi4iO319fQ==',
            'expectedResponseJweKeyAlgorithm' => 'RSA-OAEP',
            'expectedResponseJweContentAlgorithm' => 'A192GCM',
            'expectedResponseJweCompression' => 'DEF',
            'expectedResponseJweRecipientJwkKeys' => ['alg', 'use', 'kty', 'n', 'e', 'd', 'p', 'q', 'dp', 'dq', 'qi'],
            'expectedResponseJweRecipientJwkLength' => 342,
            'expectedResponseJweRecipientJwkLengthKey' => 'n',
            'expectedOutputRequestJwkKeys' => ['alg', 'use', 'kty', 'crv', 'x'],
            'expectedOutputResponseRecipientJwkKeys' => ['alg', 'use', 'kty', 'n', 'e'],
        ];

        yield 'Algorithms: ES512, RS512, ECDH-ES+A192KW, A192CBC-HS384, ECDH-ES+A256KW' => [
            'httpsOnly' => true,
            'input' => [
                'endpoint' => 'https://endpoint.test',
                'request' => [
                    'jws' => [
                        'alg' => 'ES512',
                    ],
                    'jwe' => [
                        'alg' => 'ECDH-ES+A192KW',
                        'enc' => 'A192CBC-HS384',
                        'jwk' => [
                            'kty' => 'EC',
                            'n' => '...',
                        ],
                    ],
                ],
                'response' => [
                    'jws' => [
                        'alg' => 'RS512',
                        'jwk' => [
                            'kty' => 'RSA',
                            'n' => '...',
                        ],
                    ],
                    'jwe' => [
                        'alg' => 'ECDH-ES+A256KW',
                        'enc' => 'A256CBC-HS512',
                        'zip' => 'DEF',
                    ],
                ],
            ],
            'expectedEndpoint' => 'https://endpoint.test',
            'expectedClaims' => null,
            'expectedRequestJwsAlgorithm' => 'ES512',
            'expectedRequestJwsJwkKeys' => ['alg', 'use', 'kty', 'crv', 'd', 'x', 'y'],
            'expectedRequestJwsJwkLength' => 88,
            'expectedRequestJwsJwkLengthKey' => 'd',
            'expectedSerializedRequestJweConfig' => 'Tzo1MDoiU2luZ2xlQVxCdW5kbGVzXEp3dEZldGNoZXJcRmVhdHVyZUNvbmZpZ1xKd2VDb25maWciOjQ6e3M6MToiYSI7czoxNDoiRUNESC1FUytBMTkyS1ciO3M6MToiYyI7czoxMzoiQTE5MkNCQy1IUzM4NCI7czoxOiJ6IjtOO3M6MToiayI7TzoyMzoiSm9zZVxDb21wb25lbnRcQ29yZVxKV0siOjE6e3M6MzE6IgBKb3NlXENvbXBvbmVudFxDb3JlXEpXSwB2YWx1ZXMiO2E6Mjp7czozOiJrdHkiO3M6MjoiRUMiO3M6MToibiI7czozOiIuLi4iO319fQ==',
            'expectedRequestOptions' => null,
            'expectedSerializedResponseJwsConfig' => 'Tzo1MDoiU2luZ2xlQVxCdW5kbGVzXEp3dEZldGNoZXJcRmVhdHVyZUNvbmZpZ1xKd3NDb25maWciOjI6e3M6MToiYSI7czo1OiJSUzUxMiI7czoxOiJrIjtPOjIzOiJKb3NlXENvbXBvbmVudFxDb3JlXEpXSyI6MTp7czozMToiAEpvc2VcQ29tcG9uZW50XENvcmVcSldLAHZhbHVlcyI7YToyOntzOjM6Imt0eSI7czozOiJSU0EiO3M6MToibiI7czozOiIuLi4iO319fQ==',
            'expectedResponseJweKeyAlgorithm' => 'ECDH-ES+A256KW',
            'expectedResponseJweContentAlgorithm' => 'A256CBC-HS512',
            'expectedResponseJweCompression' => 'DEF',
            'expectedResponseJweRecipientJwkKeys' => ['alg', 'use', 'kty', 'crv', 'd', 'x'],
            'expectedResponseJweRecipientJwkLength' => 22,
            'expectedResponseJweRecipientJwkLengthKey' => 'd',
            'expectedOutputRequestJwkKeys' => ['alg', 'use', 'kty', 'crv', 'x', 'y'],
            'expectedOutputResponseRecipientJwkKeys' => ['alg', 'use', 'kty', 'crv', 'x'],
        ];

        yield 'Algorithms: PS256, RSA-OAEP-256, A128CCM-16-128, PS384' => [
            'httpsOnly' => true,
            'input' => [
                'endpoint' => 'https://endpoint.test',
                'request' => [
                    'jws' => [
                        'alg' => 'PS256',
                    ],
                    'jwe' => [
                        'alg' => 'RSA-OAEP-256',
                        'enc' => 'A128CCM-16-128',
                        'jwk' => [
                            'kty' => 'RSA',
                            'n' => '...',
                        ],
                    ],
                ],
                'response' => [
                    'jws' => [
                        'alg' => 'PS384',
                        'jwk' => [
                            'kty' => 'RSA',
                            'n' => '...',
                        ],
                    ],
                    'jwe' => [
                        'alg' => 'RSA-OAEP-384',
                        'enc' => 'A128CCM-64-64',
                        'zip' => 'DEF',
                    ],
                ],
            ],
            'expectedEndpoint' => 'https://endpoint.test',
            'expectedClaims' => null,
            'expectedRequestJwsAlgorithm' => 'PS256',
            'expectedRequestJwsJwkKeys' => ['alg', 'use', 'kty', 'n', 'e', 'd', 'p', 'q', 'dp', 'dq', 'qi'],
            'expectedRequestJwsJwkLength' => 342,
            'expectedRequestJwsJwkLengthKey' => 'n',
            'expectedSerializedRequestJweConfig' => 'Tzo1MDoiU2luZ2xlQVxCdW5kbGVzXEp3dEZldGNoZXJcRmVhdHVyZUNvbmZpZ1xKd2VDb25maWciOjQ6e3M6MToiYSI7czoxMjoiUlNBLU9BRVAtMjU2IjtzOjE6ImMiO3M6MTQ6IkExMjhDQ00tMTYtMTI4IjtzOjE6InoiO047czoxOiJrIjtPOjIzOiJKb3NlXENvbXBvbmVudFxDb3JlXEpXSyI6MTp7czozMToiAEpvc2VcQ29tcG9uZW50XENvcmVcSldLAHZhbHVlcyI7YToyOntzOjM6Imt0eSI7czozOiJSU0EiO3M6MToibiI7czozOiIuLi4iO319fQ==',
            'expectedRequestOptions' => null,
            'expectedSerializedResponseJwsConfig' => 'Tzo1MDoiU2luZ2xlQVxCdW5kbGVzXEp3dEZldGNoZXJcRmVhdHVyZUNvbmZpZ1xKd3NDb25maWciOjI6e3M6MToiYSI7czo1OiJQUzM4NCI7czoxOiJrIjtPOjIzOiJKb3NlXENvbXBvbmVudFxDb3JlXEpXSyI6MTp7czozMToiAEpvc2VcQ29tcG9uZW50XENvcmVcSldLAHZhbHVlcyI7YToyOntzOjM6Imt0eSI7czozOiJSU0EiO3M6MToibiI7czozOiIuLi4iO319fQ==',
            'expectedResponseJweKeyAlgorithm' => 'RSA-OAEP-384',
            'expectedResponseJweContentAlgorithm' => 'A128CCM-64-64',
            'expectedResponseJweCompression' => 'DEF',
            'expectedResponseJweRecipientJwkKeys' => ['alg', 'use', 'kty', 'n', 'e', 'd', 'p', 'q', 'dp', 'dq', 'qi'],
            'expectedResponseJweRecipientJwkLength' => 512,
            'expectedResponseJweRecipientJwkLengthKey' => 'n',
            'expectedOutputRequestJwkKeys' => ['alg', 'use', 'kty', 'n', 'e'],
            'expectedOutputResponseRecipientJwkKeys' => ['alg', 'use', 'kty', 'n', 'e'],
        ];

        yield 'Algorithms: PS512, RSA-OAEP-512, HS512, A192KW, A256CCM-16-128' => [
            'httpsOnly' => true,
            'input' => [
                'endpoint' => 'https://endpoint.test',
                'request' => [
                    'jws' => [
                        'alg' => 'PS512',
                    ],
                    'jwe' => [
                        'alg' => 'RSA-OAEP-512',
                        'enc' => 'A128CCM-64-128',
                        'jwk' => [
                            'kty' => 'RSA',
                            'n' => '...',
                        ],
                    ],
                ],
                'response' => [
                    'jws' => [
                        'alg' => 'HS512',
                        'jwk' => [
                            'kty' => 'oct',
                            'n' => '...',
                        ],
                    ],
                    'jwe' => [
                        'alg' => 'A192KW',
                        'enc' => 'A256CCM-16-128',
                        'zip' => 'DEF',
                    ],
                ],
            ],
            'expectedEndpoint' => 'https://endpoint.test',
            'expectedClaims' => null,
            'expectedRequestJwsAlgorithm' => 'PS512',
            'expectedRequestJwsJwkKeys' => ['alg', 'use', 'kty', 'n', 'e', 'd', 'p', 'q', 'dp', 'dq', 'qi'],
            'expectedRequestJwsJwkLength' => 683,
            'expectedRequestJwsJwkLengthKey' => 'n',
            'expectedSerializedRequestJweConfig' => 'Tzo1MDoiU2luZ2xlQVxCdW5kbGVzXEp3dEZldGNoZXJcRmVhdHVyZUNvbmZpZ1xKd2VDb25maWciOjQ6e3M6MToiYSI7czoxMjoiUlNBLU9BRVAtNTEyIjtzOjE6ImMiO3M6MTQ6IkExMjhDQ00tNjQtMTI4IjtzOjE6InoiO047czoxOiJrIjtPOjIzOiJKb3NlXENvbXBvbmVudFxDb3JlXEpXSyI6MTp7czozMToiAEpvc2VcQ29tcG9uZW50XENvcmVcSldLAHZhbHVlcyI7YToyOntzOjM6Imt0eSI7czozOiJSU0EiO3M6MToibiI7czozOiIuLi4iO319fQ==',
            'expectedRequestOptions' => null,
            'expectedSerializedResponseJwsConfig' => 'Tzo1MDoiU2luZ2xlQVxCdW5kbGVzXEp3dEZldGNoZXJcRmVhdHVyZUNvbmZpZ1xKd3NDb25maWciOjI6e3M6MToiYSI7czo1OiJIUzUxMiI7czoxOiJrIjtPOjIzOiJKb3NlXENvbXBvbmVudFxDb3JlXEpXSyI6MTp7czozMToiAEpvc2VcQ29tcG9uZW50XENvcmVcSldLAHZhbHVlcyI7YToyOntzOjM6Imt0eSI7czozOiJvY3QiO3M6MToibiI7czozOiIuLi4iO319fQ==',
            'expectedResponseJweKeyAlgorithm' => 'A192KW',
            'expectedResponseJweContentAlgorithm' => 'A256CCM-16-128',
            'expectedResponseJweCompression' => 'DEF',
            'expectedResponseJweRecipientJwkKeys' => ['alg', 'use', 'kty', 'k'],
            'expectedResponseJweRecipientJwkLength' => 43,
            'expectedResponseJweRecipientJwkLengthKey' => 'k',
            'expectedOutputRequestJwkKeys' => ['alg', 'use', 'kty', 'n', 'e'],
            'expectedOutputResponseRecipientJwkKeys' => ['alg', 'use', 'kty', 'k'],
        ];

        yield 'Algorithms: A256KW, A256CCM-64-64, A192GCMKW, A256CCM-64-128' => [
            'httpsOnly' => true,
            'input' => [
                'endpoint' => 'https://endpoint.test',
                'request' => [
                    'jws' => [
                        'alg' => 'ES256',
                    ],
                    'jwe' => [
                        'alg' => 'A256KW',
                        'enc' => 'A256CCM-64-64',
                        'jwk' => [
                            'kty' => 'oct',
                            'k' => '...',
                        ],
                    ],
                ],
                'response' => [
                    'jws' => [
                        'alg' => 'ES256',
                        'jwk' => [
                            'kty' => 'EC',
                            'crv' => '...',
                        ],
                    ],
                    'jwe' => [
                        'alg' => 'A192GCMKW',
                        'enc' => 'A256CCM-64-128',
                        'zip' => 'DEF',
                    ],
                ],
            ],
            'expectedEndpoint' => 'https://endpoint.test',
            'expectedClaims' => null,
            'expectedRequestJwsAlgorithm' => 'ES256',
            'expectedRequestJwsJwkKeys' => ['alg', 'use', 'kty', 'crv', 'd', 'x', 'y'],
            'expectedRequestJwsJwkLength' => 43,
            'expectedRequestJwsJwkLengthKey' => 'd',
            'expectedSerializedRequestJweConfig' => 'Tzo1MDoiU2luZ2xlQVxCdW5kbGVzXEp3dEZldGNoZXJcRmVhdHVyZUNvbmZpZ1xKd2VDb25maWciOjQ6e3M6MToiYSI7czo2OiJBMjU2S1ciO3M6MToiYyI7czoxMzoiQTI1NkNDTS02NC02NCI7czoxOiJ6IjtOO3M6MToiayI7TzoyMzoiSm9zZVxDb21wb25lbnRcQ29yZVxKV0siOjE6e3M6MzE6IgBKb3NlXENvbXBvbmVudFxDb3JlXEpXSwB2YWx1ZXMiO2E6Mjp7czozOiJrdHkiO3M6Mzoib2N0IjtzOjE6ImsiO3M6MzoiLi4uIjt9fX0=',
            'expectedRequestOptions' => null,
            'expectedSerializedResponseJwsConfig' => 'Tzo1MDoiU2luZ2xlQVxCdW5kbGVzXEp3dEZldGNoZXJcRmVhdHVyZUNvbmZpZ1xKd3NDb25maWciOjI6e3M6MToiYSI7czo1OiJFUzI1NiI7czoxOiJrIjtPOjIzOiJKb3NlXENvbXBvbmVudFxDb3JlXEpXSyI6MTp7czozMToiAEpvc2VcQ29tcG9uZW50XENvcmVcSldLAHZhbHVlcyI7YToyOntzOjM6Imt0eSI7czoyOiJFQyI7czozOiJjcnYiO3M6MzoiLi4uIjt9fX0=',
            'expectedResponseJweKeyAlgorithm' => 'A192GCMKW',
            'expectedResponseJweContentAlgorithm' => 'A256CCM-64-128',
            'expectedResponseJweCompression' => 'DEF',
            'expectedResponseJweRecipientJwkKeys' => ['alg', 'use', 'kty', 'k'],
            'expectedResponseJweRecipientJwkLength' => 43,
            'expectedResponseJweRecipientJwkLengthKey' => 'k',
            'expectedOutputRequestJwkKeys' => ['alg', 'use', 'kty', 'crv', 'x', 'y'],
            'expectedOutputResponseRecipientJwkKeys' => ['alg', 'use', 'kty', 'k'],
        ];

        yield 'Algorithms: A256GCMKW, A128CTR' => [
            'httpsOnly' => true,
            'input' => [
                'endpoint' => 'https://endpoint.test',
                'request' => [
                    'jws' => [
                        'alg' => 'ES256',
                    ],
                    'jwe' => [
                        'alg' => 'A256GCMKW',
                        'enc' => 'A256CCM-64-64',
                        'jwk' => [
                            'kty' => 'oct',
                            'k' => '...',
                        ],
                    ],
                ],
                'response' => [
                    'jws' => [
                        'alg' => 'ES256',
                        'jwk' => [
                            'kty' => 'EC',
                            'crv' => '...',
                        ],
                    ],
                    'jwe' => [
                        'alg' => 'A128CTR',
                        'enc' => 'A256CCM-64-128',
                        'zip' => 'DEF',
                    ],
                ],
            ],
            'expectedEndpoint' => 'https://endpoint.test',
            'expectedClaims' => null,
            'expectedRequestJwsAlgorithm' => 'ES256',
            'expectedRequestJwsJwkKeys' => ['alg', 'use', 'kty', 'crv', 'd', 'x', 'y'],
            'expectedRequestJwsJwkLength' => 43,
            'expectedRequestJwsJwkLengthKey' => 'd',
            'expectedSerializedRequestJweConfig' => 'Tzo1MDoiU2luZ2xlQVxCdW5kbGVzXEp3dEZldGNoZXJcRmVhdHVyZUNvbmZpZ1xKd2VDb25maWciOjQ6e3M6MToiYSI7czo5OiJBMjU2R0NNS1ciO3M6MToiYyI7czoxMzoiQTI1NkNDTS02NC02NCI7czoxOiJ6IjtOO3M6MToiayI7TzoyMzoiSm9zZVxDb21wb25lbnRcQ29yZVxKV0siOjE6e3M6MzE6IgBKb3NlXENvbXBvbmVudFxDb3JlXEpXSwB2YWx1ZXMiO2E6Mjp7czozOiJrdHkiO3M6Mzoib2N0IjtzOjE6ImsiO3M6MzoiLi4uIjt9fX0=',
            'expectedRequestOptions' => null,
            'expectedSerializedResponseJwsConfig' => 'Tzo1MDoiU2luZ2xlQVxCdW5kbGVzXEp3dEZldGNoZXJcRmVhdHVyZUNvbmZpZ1xKd3NDb25maWciOjI6e3M6MToiYSI7czo1OiJFUzI1NiI7czoxOiJrIjtPOjIzOiJKb3NlXENvbXBvbmVudFxDb3JlXEpXSyI6MTp7czozMToiAEpvc2VcQ29tcG9uZW50XENvcmVcSldLAHZhbHVlcyI7YToyOntzOjM6Imt0eSI7czoyOiJFQyI7czozOiJjcnYiO3M6MzoiLi4uIjt9fX0=',
            'expectedResponseJweKeyAlgorithm' => 'A128CTR',
            'expectedResponseJweContentAlgorithm' => 'A256CCM-64-128',
            'expectedResponseJweCompression' => 'DEF',
            'expectedResponseJweRecipientJwkKeys' => ['alg', 'use', 'kty', 'k'],
            'expectedResponseJweRecipientJwkLength' => 43,
            'expectedResponseJweRecipientJwkLengthKey' => 'k',
            'expectedOutputRequestJwkKeys' => ['alg', 'use', 'kty', 'crv', 'x', 'y'],
            'expectedOutputResponseRecipientJwkKeys' => ['alg', 'use', 'kty', 'k'],
        ];

        yield 'Algorithms: A192CTR, A256CTR' => [
            'httpsOnly' => true,
            'input' => [
                'endpoint' => 'https://endpoint.test',
                'request' => [
                    'jws' => [
                        'alg' => 'ES256',
                    ],
                    'jwe' => [
                        'alg' => 'A192CTR',
                        'enc' => 'A256CCM-64-64',
                        'jwk' => [
                            'kty' => 'oct',
                            'k' => '...',
                        ],
                    ],
                ],
                'response' => [
                    'jws' => [
                        'alg' => 'ES256',
                        'jwk' => [
                            'kty' => 'EC',
                            'crv' => '...',
                        ],
                    ],
                    'jwe' => [
                        'alg' => 'A256CTR',
                        'enc' => 'A256CCM-64-128',
                        'zip' => 'DEF',
                    ],
                ],
            ],
            'expectedEndpoint' => 'https://endpoint.test',
            'expectedClaims' => null,
            'expectedRequestJwsAlgorithm' => 'ES256',
            'expectedRequestJwsJwkKeys' => ['alg', 'use', 'kty', 'crv', 'd', 'x', 'y'],
            'expectedRequestJwsJwkLength' => 43,
            'expectedRequestJwsJwkLengthKey' => 'd',
            'expectedSerializedRequestJweConfig' => 'Tzo1MDoiU2luZ2xlQVxCdW5kbGVzXEp3dEZldGNoZXJcRmVhdHVyZUNvbmZpZ1xKd2VDb25maWciOjQ6e3M6MToiYSI7czo3OiJBMTkyQ1RSIjtzOjE6ImMiO3M6MTM6IkEyNTZDQ00tNjQtNjQiO3M6MToieiI7TjtzOjE6ImsiO086MjM6Ikpvc2VcQ29tcG9uZW50XENvcmVcSldLIjoxOntzOjMxOiIASm9zZVxDb21wb25lbnRcQ29yZVxKV0sAdmFsdWVzIjthOjI6e3M6Mzoia3R5IjtzOjM6Im9jdCI7czoxOiJrIjtzOjM6Ii4uLiI7fX19',
            'expectedRequestOptions' => null,
            'expectedSerializedResponseJwsConfig' => 'Tzo1MDoiU2luZ2xlQVxCdW5kbGVzXEp3dEZldGNoZXJcRmVhdHVyZUNvbmZpZ1xKd3NDb25maWciOjI6e3M6MToiYSI7czo1OiJFUzI1NiI7czoxOiJrIjtPOjIzOiJKb3NlXENvbXBvbmVudFxDb3JlXEpXSyI6MTp7czozMToiAEpvc2VcQ29tcG9uZW50XENvcmVcSldLAHZhbHVlcyI7YToyOntzOjM6Imt0eSI7czoyOiJFQyI7czozOiJjcnYiO3M6MzoiLi4uIjt9fX0=',
            'expectedResponseJweKeyAlgorithm' => 'A256CTR',
            'expectedResponseJweContentAlgorithm' => 'A256CCM-64-128',
            'expectedResponseJweCompression' => 'DEF',
            'expectedResponseJweRecipientJwkKeys' => ['alg', 'use', 'kty', 'k'],
            'expectedResponseJweRecipientJwkLength' => 43,
            'expectedResponseJweRecipientJwkLengthKey' => 'k',
            'expectedOutputRequestJwkKeys' => ['alg', 'use', 'kty', 'crv', 'x', 'y'],
            'expectedOutputResponseRecipientJwkKeys' => ['alg', 'use', 'kty', 'k'],
        ];

        yield 'Algorithm chacha20-poly1305' => [
            'httpsOnly' => true,
            'input' => [
                'endpoint' => 'https://endpoint.test',
                'request' => [
                    'jws' => [
                        'alg' => 'ES256',
                    ],
                ],
                'response' => [
                    'jws' => [
                        'alg' => 'ES256',
                        'jwk' => [
                            'kty' => 'EC',
                            'crv' => '...',
                        ],
                    ],
                    'jwe' => [
                        'alg' => 'chacha20-poly1305',
                        'enc' => 'A256CCM-64-128',
                        'zip' => 'DEF',
                    ],
                ],
            ],
            'expectedEndpoint' => 'https://endpoint.test',
            'expectedClaims' => null,
            'expectedRequestJwsAlgorithm' => 'ES256',
            'expectedRequestJwsJwkKeys' => ['alg', 'use', 'kty', 'crv', 'd', 'x', 'y'],
            'expectedRequestJwsJwkLength' => 43,
            'expectedRequestJwsJwkLengthKey' => 'd',
            'expectedSerializedRequestJweConfig' => 'Tjs=',
            'expectedRequestOptions' => null,
            'expectedSerializedResponseJwsConfig' => 'Tzo1MDoiU2luZ2xlQVxCdW5kbGVzXEp3dEZldGNoZXJcRmVhdHVyZUNvbmZpZ1xKd3NDb25maWciOjI6e3M6MToiYSI7czo1OiJFUzI1NiI7czoxOiJrIjtPOjIzOiJKb3NlXENvbXBvbmVudFxDb3JlXEpXSyI6MTp7czozMToiAEpvc2VcQ29tcG9uZW50XENvcmVcSldLAHZhbHVlcyI7YToyOntzOjM6Imt0eSI7czoyOiJFQyI7czozOiJjcnYiO3M6MzoiLi4uIjt9fX0=',
            'expectedResponseJweKeyAlgorithm' => 'chacha20-poly1305',
            'expectedResponseJweContentAlgorithm' => 'A256CCM-64-128',
            'expectedResponseJweCompression' => 'DEF',
            'expectedResponseJweRecipientJwkKeys' => ['alg', 'use', 'kty', 'k'],
            'expectedResponseJweRecipientJwkLength' => 43,
            'expectedResponseJweRecipientJwkLengthKey' => 'k',
            'expectedOutputRequestJwkKeys' => ['alg', 'use', 'kty', 'crv', 'x', 'y'],
            'expectedOutputResponseRecipientJwkKeys' => ['alg', 'use', 'kty', 'k'],
        ];
    }

    /**
     * @dataProvider invalidCreateProvider
     */
    public function testInvalidCreate(array $input, bool $httpsOnly, string $expectedMessage): void
    {
        $factory = new JwtFetcherConfigFactory($httpsOnly, self::$algorithmManagerFactory, self::$compressionMethodManagerFactory);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage($expectedMessage);

        $factory->create($input);
    }

    public function invalidCreateProvider(): \Generator
    {
        yield 'Without "endpoint"' => [
            'input' => [
                'request' => [
                    'jws' => [
                        'alg' => 'ES256',
                    ],
                ],
                'response' => [
                    'jws' => [
                        'alg' => 'ES256',
                        'jwk' => [
                            'kty' => 'EC',
                            'n' => '...',
                        ],
                    ],
                ],
            ],
            'httpsOnly' => true,
            'expectedMessage' => 'The "payload.endpoint" parameter is required.',
        ];

        yield 'Invalid "endpoint" type' => [
            'input' => [
                'endpoint' => false,
                'request' => [
                    'jws' => [
                        'alg' => 'ES256',
                    ],
                ],
                'response' => [
                    'jws' => [
                        'alg' => 'ES256',
                        'jwk' => [
                            'kty' => 'EC',
                            'crv' => '...',
                        ],
                    ],
                ],
            ],
            'httpsOnly' => true,
            'expectedMessage' => 'The "payload.endpoint" parameter must be a string.',
        ];

        yield 'Invalid "endpoint" value' => [
            'input' => [
                'endpoint' => 'htt://invalid value',
                'request' => [
                    'jws' => [
                        'alg' => 'ES256',
                    ],
                ],
                'response' => [
                    'jws' => [
                        'alg' => 'ES256',
                        'jwk' => [
                            'kty' => 'EC',
                            'crv' => '...',
                        ],
                    ],
                ],
            ],
            'httpsOnly' => false,
            'expectedMessage' => 'The "payload.endpoint" parameter must be an URL with http scheme.',
        ];

        yield 'HTTP endpoint' => [
            'input' => [
                'endpoint' => 'http://endpoint.test',
                'request' => [
                    'jws' => [
                        'alg' => 'ES256',
                    ],
                ],
                'response' => [
                    'jws' => [
                        'alg' => 'ES256',
                        'jwk' => [
                            'kty' => 'EC',
                            'crv' => '...',
                        ],
                    ],
                ],
            ],
            'httpsOnly' => true,
            'expectedMessage' => 'The "payload.endpoint" parameter must be an URL with https scheme.',
        ];

        yield 'Invalid "claims" type' => [
            'input' => [
                'endpoint' => 'https://endpoint.test',
                'claims' => null,
                'request' => [
                    'jws' => [
                        'alg' => 'ES256',
                    ],
                ],
                'response' => [
                    'jws' => [
                        'alg' => 'ES256',
                        'jwk' => [
                            'kty' => 'EC',
                            'crv' => '...',
                        ],
                    ],
                ],
            ],
            'httpsOnly' => true,
            'expectedMessage' => 'The "payload.claims" parameter must be an array.',
        ];

        yield 'Without "request"' => [
            'input' => [
                'endpoint' => 'https://endpoint.test',
                'response' => [
                    'jws' => [
                        'alg' => 'ES256',
                        'jwk' => [
                            'kty' => 'EC',
                            'crv' => '...',
                        ],
                    ],
                ],
            ],
            'httpsOnly' => true,
            'expectedMessage' => 'The "payload.request" parameter is required.',
        ];

        yield 'Invalid "request" type' => [
            'input' => [
                'endpoint' => 'https://endpoint.test',
                'request' => 111,
                'response' => [
                    'jws' => [
                        'alg' => 'ES256',
                        'jwk' => [
                            'kty' => 'EC',
                            'crv' => '...',
                        ],
                    ],
                ],
            ],
            'httpsOnly' => true,
            'expectedMessage' => 'The "payload.request" parameter must be an array.',
        ];

        yield 'Without "request.jws"' => [
            'input' => [
                'endpoint' => 'https://endpoint.test',
                'request' => [],
                'response' => [
                    'jws' => [
                        'alg' => 'ES256',
                        'jwk' => [
                            'kty' => 'EC',
                            'crv' => '...',
                        ],
                    ],
                ],
            ],
            'httpsOnly' => true,
            'expectedMessage' => 'The "payload.request.jws" parameter is required.',
        ];

        yield 'Invalid "request.jws" type' => [
            'input' => [
                'endpoint' => 'https://endpoint.test',
                'request' => [
                    'jws' => false,
                ],
                'response' => [
                    'jws' => [
                        'alg' => 'ES256',
                        'jwk' => [
                            'kty' => 'EC',
                            'crv' => '...',
                        ],
                    ],
                ],
            ],
            'httpsOnly' => true,
            'expectedMessage' => 'The "payload.request.jws" parameter must be an array.',
        ];

        yield 'Without "request.jws.alg"' => [
            'input' => [
                'endpoint' => 'https://endpoint.test',
                'request' => [
                    'jws' => [],
                ],
                'response' => [
                    'jws' => [
                        'alg' => 'ES256',
                        'jwk' => [
                            'kty' => 'EC',
                            'crv' => '...',
                        ],
                    ],
                ],
            ],
            'httpsOnly' => true,
            'expectedMessage' => 'The "payload.request.jws.alg" parameter is required.',
        ];

        yield 'Invalid "request.jws.alg" type' => [
            'input' => [
                'endpoint' => 'https://endpoint.test',
                'request' => [
                    'jws' => [
                        'alg' => true,
                    ],
                ],
                'response' => [
                    'jws' => [
                        'alg' => 'ES256',
                        'jwk' => [
                            'kty' => 'EC',
                            'crv' => '...',
                        ],
                    ],
                ],
            ],
            'httpsOnly' => true,
            'expectedMessage' => 'The "payload.request.jws.alg" parameter must be a string.',
        ];

        yield 'Invalid "request.jws.alg" value' => [
            'input' => [
                'endpoint' => 'https://endpoint.test',
                'request' => [
                    'jws' => [
                        'alg' => 'ES128',
                    ],
                ],
                'response' => [
                    'jws' => [
                        'alg' => 'ES256',
                        'jwk' => [
                            'kty' => 'EC',
                            'crv' => '...',
                        ],
                    ],
                ],
            ],
            'httpsOnly' => true,
            'expectedMessage' => 'The "payload.request.jws.alg" parameter contains unsupported algorithm.',
        ];

        yield 'Invalid "request.jws.bits" type' => [
            'input' => [
                'endpoint' => 'https://endpoint.test',
                'request' => [
                    'jws' => [
                        'alg' => 'RS256',
                        'bits' => 'inf',
                    ],
                ],
                'response' => [
                    'jws' => [
                        'alg' => 'ES256',
                        'jwk' => [
                            'kty' => 'EC',
                            'crv' => '...',
                        ],
                    ],
                ],
            ],
            'httpsOnly' => true,
            'expectedMessage' => 'The "payload.request.jws.bits" parameter must be a number greater than or equal to 512.',
        ];

        yield 'Invalid "request.jws.bits" value' => [
            'input' => [
                'endpoint' => 'https://endpoint.test',
                'request' => [
                    'jws' => [
                        'alg' => 'RS256',
                        'bits' => 511,
                    ],
                ],
                'response' => [
                    'jws' => [
                        'alg' => 'ES256',
                        'jwk' => [
                            'kty' => 'EC',
                            'crv' => '...',
                        ],
                    ],
                ],
            ],
            'httpsOnly' => true,
            'expectedMessage' => 'The "payload.request.jws.bits" parameter must be a number greater than or equal to 512.',
        ];

        yield 'Invalid "request.jwe" type' => [
            'input' => [
                'endpoint' => 'https://endpoint.test',
                'request' => [
                    'jws' => [
                        'alg' => 'HS256',
                    ],
                    'jwe' => null,
                ],
                'response' => [
                    'jws' => [
                        'alg' => 'ES256',
                        'jwk' => [
                            'kty' => 'EC',
                            'crv' => '...',
                        ],
                    ],
                ],
            ],
            'httpsOnly' => true,
            'expectedMessage' => 'The "payload.request.jwe" parameter must be an array.',
        ];

        yield 'Without "request.jwe.alg"' => [
            'input' => [
                'endpoint' => 'https://endpoint.test',
                'request' => [
                    'jws' => [
                        'alg' => 'HS256',
                    ],
                    'jwe' => [
                        'enc' => 'A128GCM',
                        'jwk' => [],
                    ],
                ],
                'response' => [
                    'jws' => [
                        'alg' => 'ES256',
                        'jwk' => [
                            'kty' => 'EC',
                            'crv' => '...',
                        ],
                    ],
                ],
            ],
            'httpsOnly' => true,
            'expectedMessage' => 'The "payload.request.jwe.alg" parameter is required.',
        ];

        yield 'Invalid "request.jwe.alg" type' => [
            'input' => [
                'endpoint' => 'https://endpoint.test',
                'request' => [
                    'jws' => [
                        'alg' => 'HS256',
                    ],
                    'jwe' => [
                        'alg' => false,
                        'enc' => 'A128GCM',
                        'jwk' => [],
                    ],
                ],
                'response' => [
                    'jws' => [
                        'alg' => 'ES256',
                        'jwk' => [
                            'kty' => 'EC',
                            'crv' => '...',
                        ],
                    ],
                ],
            ],
            'httpsOnly' => true,
            'expectedMessage' => 'The "payload.request.jwe.alg" parameter must be a string.',
        ];

        yield 'Invalid "request.jwe.alg" value' => [
            'input' => [
                'endpoint' => 'https://endpoint.test',
                'request' => [
                    'jws' => [
                        'alg' => 'HS256',
                    ],
                    'jwe' => [
                        'alg' => 'RSA-OAEP-64',
                        'enc' => 'A128GCM',
                        'jwk' => [],
                    ],
                ],
                'response' => [
                    'jws' => [
                        'alg' => 'ES256',
                        'jwk' => [
                            'kty' => 'EC',
                            'crv' => '...',
                        ],
                    ],
                ],
            ],
            'httpsOnly' => true,
            'expectedMessage' => 'The "payload.request.jwe.alg" parameter contains unsupported algorithm.',
        ];

        yield 'Without "request.jwe.enc"' => [
            'input' => [
                'endpoint' => 'https://endpoint.test',
                'request' => [
                    'jws' => [
                        'alg' => 'HS256',
                    ],
                    'jwe' => [
                        'alg' => 'RSA-OAEP',
                        'jwk' => [],
                    ],
                ],
                'response' => [
                    'jws' => [
                        'alg' => 'ES256',
                        'jwk' => [
                            'kty' => 'EC',
                            'crv' => '...',
                        ],
                    ],
                ],
            ],
            'httpsOnly' => true,
            'expectedMessage' => 'The "payload.request.jwe.enc" parameter is required.',
        ];

        yield 'Invalid "request.jwe.enc" type' => [
            'input' => [
                'endpoint' => 'https://endpoint.test',
                'request' => [
                    'jws' => [
                        'alg' => 'HS256',
                    ],
                    'jwe' => [
                        'alg' => 'RSA-OAEP',
                        'enc' => true,
                        'jwk' => [],
                    ],
                ],
                'response' => [
                    'jws' => [
                        'alg' => 'ES256',
                        'jwk' => [
                            'kty' => 'EC',
                            'crv' => '...',
                        ],
                    ],
                ],
            ],
            'httpsOnly' => true,
            'expectedMessage' => 'The "payload.request.jwe.enc" parameter must be a string.',
        ];

        yield 'Invalid "request.jwe.enc" value' => [
            'input' => [
                'endpoint' => 'https://endpoint.test',
                'request' => [
                    'jws' => [
                        'alg' => 'HS256',
                    ],
                    'jwe' => [
                        'alg' => 'RSA-OAEP-64',
                        'enc' => 'A64GCM',
                        'jwk' => [],
                    ],
                ],
                'response' => [
                    'jws' => [
                        'alg' => 'ES256',
                        'jwk' => [
                            'kty' => 'EC',
                            'crv' => '...',
                        ],
                    ],
                ],
            ],
            'httpsOnly' => true,
            'expectedMessage' => 'The "payload.request.jwe.enc" parameter contains unsupported algorithm.',
        ];

        yield 'Invalid "request.jwe.zip" value' => [
            'input' => [
                'endpoint' => 'https://endpoint.test',
                'request' => [
                    'jws' => [
                        'alg' => 'HS256',
                    ],
                    'jwe' => [
                        'alg' => 'RSA-OAEP',
                        'enc' => 'A128GCM',
                        'zip' => 'BR',
                        'jwk' => [],
                    ],
                ],
                'response' => [
                    'jws' => [
                        'alg' => 'ES256',
                        'jwk' => [
                            'kty' => 'EC',
                            'crv' => '...',
                        ],
                    ],
                ],
            ],
            'httpsOnly' => true,
            'expectedMessage' => 'The "payload.request.jwe.zip" parameter contains unsupported compression method.',
        ];

        yield 'Without "request.jwe.jwk"' => [
            'input' => [
                'endpoint' => 'https://endpoint.test',
                'request' => [
                    'jws' => [
                        'alg' => 'HS256',
                    ],
                    'jwe' => [
                        'alg' => 'RSA-OAEP',
                        'enc' => 'A128GCM',
                    ],
                ],
                'response' => [
                    'jws' => [
                        'alg' => 'ES256',
                        'jwk' => [
                            'kty' => 'EC',
                            'crv' => '...',
                        ],
                    ],
                ],
            ],
            'httpsOnly' => true,
            'expectedMessage' => 'The "payload.request.jwe.jwk" parameter is required.',
        ];

        yield 'Invalid "request.jwe.jwk" type' => [
            'input' => [
                'endpoint' => 'https://endpoint.test',
                'request' => [
                    'jws' => [
                        'alg' => 'HS256',
                    ],
                    'jwe' => [
                        'alg' => 'RSA-OAEP',
                        'enc' => 'A128GCM',
                        'jwk' => null,
                    ],
                ],
                'response' => [
                    'jws' => [
                        'alg' => 'ES256',
                        'jwk' => [
                            'kty' => 'EC',
                            'crv' => '...',
                        ],
                    ],
                ],
            ],
            'httpsOnly' => true,
            'expectedMessage' => 'The "payload.request.jwe.jwk" parameter must be an array.',
        ];

        yield 'Invalid "request.jwe.jwk" value' => [
            'input' => [
                'endpoint' => 'https://endpoint.test',
                'request' => [
                    'jws' => [
                        'alg' => 'HS256',
                    ],
                    'jwe' => [
                        'alg' => 'RSA-OAEP',
                        'enc' => 'A128GCM',
                        'jwk' => [],
                    ],
                ],
                'response' => [
                    'jws' => [
                        'alg' => 'ES256',
                        'jwk' => [
                            'kty' => 'EC',
                            'crv' => '...',
                        ],
                    ],
                ],
            ],
            'httpsOnly' => true,
            'expectedMessage' => 'The "payload.request.jwe.jwk" parameter has invalid value: The parameter "kty" is mandatory.',
        ];

        yield 'Invalid "request.options" type' => [
            'input' => [
                'endpoint' => 'https://endpoint.test',
                'request' => [
                    'jws' => [
                        'alg' => 'ES256',
                    ],
                    'options' => 0,
                ],
                'response' => [
                    'jws' => [
                        'alg' => 'ES256',
                        'jwk' => [
                            'kty' => 'EC',
                            'crv' => '...',
                        ],
                    ],
                ],
            ],
            'httpsOnly' => true,
            'expectedMessage' => 'The "payload.request.options" parameter must be an array.',
        ];

        yield 'Without "response"' => [
            'input' => [
                'endpoint' => 'https://endpoint.test',
                'request' => [
                    'jws' => [
                        'alg' => 'ES256',
                    ],
                ],
            ],
            'httpsOnly' => true,
            'expectedMessage' => 'The "payload.response" parameter is required.',
        ];

        yield 'Invalid "response" type' => [
            'input' => [
                'endpoint' => 'https://endpoint.test',
                'request' => [
                    'jws' => [
                        'alg' => 'ES256',
                    ],
                ],
                'response' => false,
            ],
            'httpsOnly' => true,
            'expectedMessage' => 'The "payload.response" parameter must be an array.',
        ];

        yield 'Without "response.jws"' => [
            'input' => [
                'endpoint' => 'https://endpoint.test',
                'request' => [
                    'jws' => [
                        'alg' => 'ES256',
                    ],
                ],
                'response' => [],
            ],
            'httpsOnly' => true,
            'expectedMessage' => 'The "payload.response.jws" parameter is required.',
        ];

        yield 'Invalid "response.jws" type' => [
            'input' => [
                'endpoint' => 'https://endpoint.test',
                'request' => [
                    'jws' => [
                        'alg' => 'ES256',
                    ],
                ],
                'response' => [
                    'jws' => false,
                ],
            ],
            'httpsOnly' => true,
            'expectedMessage' => 'The "payload.response.jws" parameter must be an array.',
        ];

        yield 'Without "response.jws.alg"' => [
            'input' => [
                'endpoint' => 'https://endpoint.test',
                'request' => [
                    'jws' => [
                        'alg' => 'ES256',
                    ],
                ],
                'response' => [
                    'jws' => [
                        'jwk' => [
                            'kty' => 'RSA',
                            'n' => '...',
                        ],
                    ],
                ],
            ],
            'httpsOnly' => true,
            'expectedMessage' => 'The "payload.response.jws.alg" parameter is required.',
        ];

        yield 'Invalid "response.jws.alg" type' => [
            'input' => [
                'endpoint' => 'https://endpoint.test',
                'request' => [
                    'jws' => [
                        'alg' => 'ES256',
                    ],
                ],
                'response' => [
                    'jws' => [
                        'alg' => null,
                        'jwk' => [
                            'kty' => 'RSA',
                            'n' => '...',
                        ],
                    ],
                ],
            ],
            'httpsOnly' => true,
            'expectedMessage' => 'The "payload.response.jws.alg" parameter must be a string.',
        ];

        yield 'Invalid "response.jws.alg" value' => [
            'input' => [
                'endpoint' => 'https://endpoint.test',
                'request' => [
                    'jws' => [
                        'alg' => 'ES256',
                    ],
                ],
                'response' => [
                    'jws' => [
                        'alg' => 'RS64',
                        'jwk' => [
                            'kty' => 'RSA',
                            'n' => '...',
                        ],
                    ],
                ],
            ],
            'httpsOnly' => true,
            'expectedMessage' => 'The "payload.response.jws.alg" parameter contains unsupported algorithm.',
        ];

        yield 'Without "response.jws.jwk"' => [
            'input' => [
                'endpoint' => 'https://endpoint.test',
                'request' => [
                    'jws' => [
                        'alg' => 'ES256',
                    ],
                ],
                'response' => [
                    'jws' => [
                        'alg' => 'RS384',
                    ],
                ],
            ],
            'httpsOnly' => true,
            'expectedMessage' => 'The "payload.response.jws.jwk" parameter is required.',
        ];

        yield 'Invalid "response.jws.jwk" type' => [
            'input' => [
                'endpoint' => 'https://endpoint.test',
                'request' => [
                    'jws' => [
                        'alg' => 'ES256',
                    ],
                ],
                'response' => [
                    'jws' => [
                        'alg' => 'RS384',
                        'jwk' => true,
                    ],
                ],
            ],
            'httpsOnly' => true,
            'expectedMessage' => 'The "payload.response.jws.jwk" parameter must be an array.',
        ];

        yield 'Invalid "response.jws.jwk" value' => [
            'input' => [
                'endpoint' => 'https://endpoint.test',
                'request' => [
                    'jws' => [
                        'alg' => 'ES256',
                    ],
                ],
                'response' => [
                    'jws' => [
                        'alg' => 'RS384',
                        'jwk' => [],
                    ],
                ],
            ],
            'httpsOnly' => true,
            'expectedMessage' => 'The "payload.response.jws.jwk" parameter has invalid value: The parameter "kty" is mandatory.',
        ];

        yield 'Invalid "response.jwe" type' => [
            'input' => [
                'endpoint' => 'https://endpoint.test',
                'request' => [
                    'jws' => [
                        'alg' => 'ES256',
                    ],
                ],
                'response' => [
                    'jws' => [
                        'alg' => 'RS384',
                        'jwk' => [
                            'kty' => 'RSA',
                            'n' => '...',
                        ],
                    ],
                    'jwe' => false,
                ],
            ],
            'httpsOnly' => true,
            'expectedMessage' => 'The "payload.response.jwe" parameter must be an array.',
        ];

        yield 'Without "response.jwe.alg"' => [
            'input' => [
                'endpoint' => 'https://endpoint.test',
                'request' => [
                    'jws' => [
                        'alg' => 'ES256',
                    ],
                ],
                'response' => [
                    'jws' => [
                        'alg' => 'RS384',
                        'jwk' => [
                            'kty' => 'RSA',
                            'n' => '...',
                        ],
                    ],
                    'jwe' => [
                        'enc' => 'A256CCM-64-128',
                    ],
                ],
            ],
            'httpsOnly' => true,
            'expectedMessage' => 'The "payload.response.jwe.alg" parameter is required.',
        ];

        yield 'Invalid "response.jwe.alg" type' => [
            'input' => [
                'endpoint' => 'https://endpoint.test',
                'request' => [
                    'jws' => [
                        'alg' => 'ES256',
                    ],
                ],
                'response' => [
                    'jws' => [
                        'alg' => 'RS384',
                        'jwk' => [
                            'kty' => 'RSA',
                            'n' => '...',
                        ],
                    ],
                    'jwe' => [
                        'alg' => null,
                        'enc' => 'A256CCM-64-128',
                    ],
                ],
            ],
            'httpsOnly' => true,
            'expectedMessage' => 'The "payload.response.jwe.alg" parameter must be a string.',
        ];

        yield 'Invalid "response.jwe.alg" value' => [
            'input' => [
                'endpoint' => 'https://endpoint.test',
                'request' => [
                    'jws' => [
                        'alg' => 'ES256',
                    ],
                ],
                'response' => [
                    'jws' => [
                        'alg' => 'RS384',
                        'jwk' => [
                            'kty' => 'RSA',
                            'n' => '...',
                        ],
                    ],
                    'jwe' => [
                        'alg' => 'A32CTR',
                        'enc' => 'A256CCM-64-128',
                    ],
                ],
            ],
            'httpsOnly' => true,
            'expectedMessage' => 'The "payload.response.jwe.alg" parameter contains unsupported algorithm.',
        ];

        yield 'Without "response.jwe.enc"' => [
            'input' => [
                'endpoint' => 'https://endpoint.test',
                'request' => [
                    'jws' => [
                        'alg' => 'ES256',
                    ],
                ],
                'response' => [
                    'jws' => [
                        'alg' => 'RS384',
                        'jwk' => [
                            'kty' => 'RSA',
                            'n' => '...',
                        ],
                    ],
                    'jwe' => [
                        'alg' => 'A128CTR',
                    ],
                ],
            ],
            'httpsOnly' => true,
            'expectedMessage' => 'The "payload.response.jwe.enc" parameter is required.',
        ];

        yield 'Invalid "response.jwe.enc" type' => [
            'input' => [
                'endpoint' => 'https://endpoint.test',
                'request' => [
                    'jws' => [
                        'alg' => 'ES256',
                    ],
                ],
                'response' => [
                    'jws' => [
                        'alg' => 'RS384',
                        'jwk' => [
                            'kty' => 'RSA',
                            'n' => '...',
                        ],
                    ],
                    'jwe' => [
                        'alg' => 'A128CTR',
                        'enc' => 10,
                    ],
                ],
            ],
            'httpsOnly' => true,
            'expectedMessage' => 'The "payload.response.jwe.enc" parameter must be a string.',
        ];

        yield 'Invalid "response.jwe.enc" value' => [
            'input' => [
                'endpoint' => 'https://endpoint.test',
                'request' => [
                    'jws' => [
                        'alg' => 'ES256',
                    ],
                ],
                'response' => [
                    'jws' => [
                        'alg' => 'RS384',
                        'jwk' => [
                            'kty' => 'RSA',
                            'n' => '...',
                        ],
                    ],
                    'jwe' => [
                        'alg' => 'A128CTR',
                        'enc' => 'A64CCM-64-64',
                    ],
                ],
            ],
            'httpsOnly' => true,
            'expectedMessage' => 'The "payload.response.jwe.enc" parameter contains unsupported algorithm.',
        ];

        yield 'Invalid "response.jwe.zip" value' => [
            'input' => [
                'endpoint' => 'https://endpoint.test',
                'request' => [
                    'jws' => [
                        'alg' => 'ES256',
                    ],
                ],
                'response' => [
                    'jws' => [
                        'alg' => 'RS384',
                        'jwk' => [
                            'kty' => 'RSA',
                            'n' => '...',
                        ],
                    ],
                    'jwe' => [
                        'alg' => 'A128CTR',
                        'enc' => 'A128CCM-16-64',
                        'zip' => 'BR',
                    ],
                ],
            ],
            'httpsOnly' => true,
            'expectedMessage' => 'The "payload.response.jwe.zip" parameter contains unsupported compression method.',
        ];

        yield 'Multiple errors' => [
            'input' => [
                'endpoint' => 'ttp://e',
                'claims' => true,
                'options' => false,
                'request' => [],
                'response' => [],
            ],
            'httpsOnly' => true,
            'expectedMessage' => implode("\n", [
                'The "payload.endpoint" parameter must be an URL with https scheme.',
                'The "payload.claims" parameter must be an array.',
                'The "payload.request.jws" parameter is required.',
                'The "payload.response.jws" parameter is required.',
            ]),
        ];
    }
}
