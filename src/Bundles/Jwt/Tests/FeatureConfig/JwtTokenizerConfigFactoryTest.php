<?php declare(strict_types=1);
// SPDX-License-Identifier: BSD-3-Clause

namespace SingleA\Bundles\Jwt\Tests\FeatureConfig;

use Jose\Component\Core\AlgorithmManagerFactory;
use Jose\Component\Core\JWK;
use Jose\Component\Encryption;
use Jose\Component\Signature;
use PHPUnit\Framework\TestCase;
use SingleA\Bundles\Jwt\FeatureConfig\JwtTokenizerConfigFactory;

/**
 * @covers \SingleA\Bundles\Jwt\FeatureConfig\JwtTokenizerConfigFactory
 *
 * @internal
 */
final class JwtTokenizerConfigFactoryTest extends TestCase
{
    private const DEFAULT_TTL = 300;

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
        self::$algorithmManagerFactory->add('RSA-OAEP', new Encryption\Algorithm\KeyEncryption\RSAOAEP());
        self::$algorithmManagerFactory->add('RSA-OAEP-256', new Encryption\Algorithm\KeyEncryption\RSAOAEP256());
        self::$algorithmManagerFactory->add('A128GCM', new Encryption\Algorithm\ContentEncryption\A128GCM());
        self::$algorithmManagerFactory->add('A192GCM', new Encryption\Algorithm\ContentEncryption\A192GCM());

        self::$compressionMethodManagerFactory = new Encryption\Compression\CompressionMethodManagerFactory();
        self::$compressionMethodManagerFactory->add('DEF', new Encryption\Compression\Deflate());
    }

    public function testStrings(): void
    {
        $factory = new JwtTokenizerConfigFactory(null, self::$algorithmManagerFactory, self::$compressionMethodManagerFactory);

        self::assertSame('SingleA\Bundles\Jwt\FeatureConfig\JwtTokenizerConfig', $factory->getConfigClass());
        self::assertSame('token', $factory->getKey());
        self::assertSame('jwt', $factory->getHash());
    }

    /**
     * @dataProvider successfulProvider
     */
    public function testSuccessfulCreate(
        array $input,
        ?int $expectedTtl,
        ?array $expectedClaims,
        string $expectedJwsConfigAlgorithm,
        array $expectedJwsConfigJwkKeys,
        string $expectedSerializedJweConfig,
        ?string $expectedAudience,
        array $expectedOutputJwkKeys,
        ?int $defaultTtl,
    ): void {
        $output = null;
        $factory = new JwtTokenizerConfigFactory($defaultTtl, self::$algorithmManagerFactory, self::$compressionMethodManagerFactory);
        $config = $factory->create($input, $output);

        self::assertSame($expectedTtl, $config->getTtl());
        self::assertSame($expectedClaims, $config->getClaims());
        self::assertSame($expectedSerializedJweConfig, base64_encode(serialize($config->getJweConfig())));
        self::assertSame($expectedAudience, $config->getAudience());

        $jwsConfig = $config->getJwsConfig();
        self::assertSame($expectedJwsConfigAlgorithm, $jwsConfig->getAlgorithm());

        $jwsJwkValues = $jwsConfig->getJwk()->all();
        foreach ($expectedJwsConfigJwkKeys as $key) {
            self::assertArrayHasKey($key, $jwsJwkValues);
        }

        self::assertIsArray($output);
        self::assertArrayHasKey('jwk', $output);
        self::assertInstanceOf(JWK::class, $output['jwk']);
        $publicJwkValues = $output['jwk']->all();
        foreach ($expectedOutputJwkKeys as $key) {
            self::assertArrayHasKey($key, $publicJwkValues);
        }
    }

    public function successfulProvider(): \Generator
    {
        yield 'TTL, no claims, JWS, JWE (no zip), no audience' => [
            'input' => [
                'ttl' => 60,
                'jws' => [
                    'alg' => 'ES256',
                ],
                'jwe' => [
                    'alg' => 'RSA-OAEP',
                    'enc' => 'A128GCM',
                    'jwk' => [
                        'alg' => 'RSA-OAEP',
                        'use' => 'enc',
                        'kty' => 'RSA',
                        'n' => '...',
                        'e' => '...',
                    ],
                ],
            ],
            'expectedTtl' => 60,
            'expectedClaims' => null,
            'expectedJwsConfigAlgorithm' => 'ES256',
            'expectedJwsConfigJwkKeys' => ['alg', 'use', 'kty', 'crv', 'd', 'x', 'y'],
            'expectedSerializedJweConfig' => 'Tzo0MzoiU2luZ2xlQVxCdW5kbGVzXEp3dFxGZWF0dXJlQ29uZmlnXEp3ZUNvbmZpZyI6NDp7czoxOiJhIjtzOjg6IlJTQS1PQUVQIjtzOjE6ImMiO3M6NzoiQTEyOEdDTSI7czoxOiJ6IjtOO3M6MToiayI7TzoyMzoiSm9zZVxDb21wb25lbnRcQ29yZVxKV0siOjE6e3M6MzE6IgBKb3NlXENvbXBvbmVudFxDb3JlXEpXSwB2YWx1ZXMiO2E6NTp7czozOiJhbGciO3M6ODoiUlNBLU9BRVAiO3M6MzoidXNlIjtzOjM6ImVuYyI7czozOiJrdHkiO3M6MzoiUlNBIjtzOjE6Im4iO3M6MzoiLi4uIjtzOjE6ImUiO3M6MzoiLi4uIjt9fX0=',
            'expectedAudience' => null,
            'expectedOutputJwkKeys' => ['alg', 'use', 'kty', 'crv', 'x', 'y'],
            'defaultTtl' => self::DEFAULT_TTL,
        ];

        yield 'No TTL (without default), claims, JWS, JWE (with zip), audience' => [
            'input' => [
                'claims' => ['username', 'email'],
                'jws' => [
                    'alg' => 'RS256',
                    'bits' => 2048,
                ],
                'jwe' => [
                    'alg' => 'RSA-OAEP-256',
                    'enc' => 'A192GCM',
                    'zip' => 'DEF',
                    'jwk' => [
                        'alg' => 'RSA-OAEP-256',
                        'use' => 'enc',
                        'kty' => 'RSA',
                        'n' => '...',
                        'e' => '...',
                    ],
                ],
                'aud' => 'test-app',
            ],
            'expectedTtl' => null,
            'expectedClaims' => ['username', 'email'],
            'expectedJwsConfigAlgorithm' => 'RS256',
            'expectedJwsConfigJwkKeys' => ['alg', 'use', 'kty', 'n', 'e', 'd', 'p', 'q', 'dp', 'dq', 'qi'],
            'expectedSerializedJweConfig' => 'Tzo0MzoiU2luZ2xlQVxCdW5kbGVzXEp3dFxGZWF0dXJlQ29uZmlnXEp3ZUNvbmZpZyI6NDp7czoxOiJhIjtzOjEyOiJSU0EtT0FFUC0yNTYiO3M6MToiYyI7czo3OiJBMTkyR0NNIjtzOjE6InoiO3M6MzoiREVGIjtzOjE6ImsiO086MjM6Ikpvc2VcQ29tcG9uZW50XENvcmVcSldLIjoxOntzOjMxOiIASm9zZVxDb21wb25lbnRcQ29yZVxKV0sAdmFsdWVzIjthOjU6e3M6MzoiYWxnIjtzOjEyOiJSU0EtT0FFUC0yNTYiO3M6MzoidXNlIjtzOjM6ImVuYyI7czozOiJrdHkiO3M6MzoiUlNBIjtzOjE6Im4iO3M6MzoiLi4uIjtzOjE6ImUiO3M6MzoiLi4uIjt9fX0=',
            'expectedAudience' => 'test-app',
            'expectedOutputJwkKeys' => ['alg', 'use', 'kty', 'n', 'e'],
            'defaultTtl' => null,
        ];

        yield 'Only JWS: ES512' => [
            'input' => [
                'jws' => [
                    'alg' => 'ES512',
                ],
            ],
            'expectedTtl' => self::DEFAULT_TTL,
            'expectedClaims' => null,
            'expectedJwsConfigAlgorithm' => 'ES512',
            'expectedJwsConfigJwkKeys' => ['alg', 'use', 'kty', 'crv', 'd', 'x', 'y'],
            'expectedSerializedJweConfig' => 'Tjs=',
            'expectedAudience' => null,
            'expectedOutputJwkKeys' => ['alg', 'use', 'kty', 'crv', 'x', 'y'],
            'defaultTtl' => self::DEFAULT_TTL,
        ];

        yield 'Only JWS: ES256K' => [
            'input' => [
                'jws' => [
                    'alg' => 'ES256K',
                ],
            ],
            'expectedTtl' => self::DEFAULT_TTL,
            'expectedClaims' => null,
            'expectedJwsConfigAlgorithm' => 'ES256K',
            'expectedJwsConfigJwkKeys' => ['alg', 'use', 'kty', 'crv', 'd', 'x', 'y'],
            'expectedSerializedJweConfig' => 'Tjs=',
            'expectedAudience' => null,
            'expectedOutputJwkKeys' => ['alg', 'use', 'kty', 'crv', 'x', 'y'],
            'defaultTtl' => self::DEFAULT_TTL,
        ];

        yield 'Only JWS: EdDSA' => [
            'input' => [
                'jws' => [
                    'alg' => 'EdDSA',
                ],
            ],
            'expectedTtl' => self::DEFAULT_TTL,
            'expectedClaims' => null,
            'expectedJwsConfigAlgorithm' => 'EdDSA',
            'expectedJwsConfigJwkKeys' => ['alg', 'use', 'kty', 'crv', 'd', 'x'],
            'expectedSerializedJweConfig' => 'Tjs=',
            'expectedAudience' => null,
            'expectedOutputJwkKeys' => ['alg', 'use', 'kty', 'crv', 'x'],
            'defaultTtl' => self::DEFAULT_TTL,
        ];

        yield 'Only JWS: RS256' => [
            'input' => [
                'jws' => [
                    'alg' => 'RS256',
                ],
            ],
            'expectedTtl' => self::DEFAULT_TTL,
            'expectedClaims' => null,
            'expectedJwsConfigAlgorithm' => 'RS256',
            'expectedJwsConfigJwkKeys' => ['alg', 'use', 'kty', 'n', 'e', 'd', 'p', 'q', 'dp', 'dq', 'qi'],
            'expectedSerializedJweConfig' => 'Tjs=',
            'expectedAudience' => null,
            'expectedOutputJwkKeys' => ['alg', 'use', 'kty', 'n', 'e'],
            'defaultTtl' => self::DEFAULT_TTL,
        ];

        yield 'Only JWS: RS384' => [
            'input' => [
                'jws' => [
                    'alg' => 'RS384',
                ],
            ],
            'expectedTtl' => self::DEFAULT_TTL,
            'expectedClaims' => null,
            'expectedJwsConfigAlgorithm' => 'RS384',
            'expectedJwsConfigJwkKeys' => ['alg', 'use', 'kty', 'n', 'e', 'd', 'p', 'q', 'dp', 'dq', 'qi'],
            'expectedSerializedJweConfig' => 'Tjs=',
            'expectedAudience' => null,
            'expectedOutputJwkKeys' => ['alg', 'use', 'kty', 'n', 'e'],
            'defaultTtl' => self::DEFAULT_TTL,
        ];

        yield 'Only JWS: RS512' => [
            'input' => [
                'jws' => [
                    'alg' => 'RS512',
                ],
            ],
            'expectedTtl' => self::DEFAULT_TTL,
            'expectedClaims' => null,
            'expectedJwsConfigAlgorithm' => 'RS512',
            'expectedJwsConfigJwkKeys' => ['alg', 'use', 'kty', 'n', 'e', 'd', 'p', 'q', 'dp', 'dq', 'qi'],
            'expectedSerializedJweConfig' => 'Tjs=',
            'expectedAudience' => null,
            'expectedOutputJwkKeys' => ['alg', 'use', 'kty', 'n', 'e'],
            'defaultTtl' => self::DEFAULT_TTL,
        ];

        yield 'Only JWS: PS256' => [
            'input' => [
                'jws' => [
                    'alg' => 'PS256',
                ],
            ],
            'expectedTtl' => self::DEFAULT_TTL,
            'expectedClaims' => null,
            'expectedJwsConfigAlgorithm' => 'PS256',
            'expectedJwsConfigJwkKeys' => ['alg', 'use', 'kty', 'n', 'e', 'd', 'p', 'q', 'dp', 'dq', 'qi'],
            'expectedSerializedJweConfig' => 'Tjs=',
            'expectedAudience' => null,
            'expectedOutputJwkKeys' => ['alg', 'use', 'kty', 'n', 'e'],
            'defaultTtl' => self::DEFAULT_TTL,
        ];

        yield 'Only JWS: PS384' => [
            'input' => [
                'jws' => [
                    'alg' => 'PS384',
                ],
            ],
            'expectedTtl' => self::DEFAULT_TTL,
            'expectedClaims' => null,
            'expectedJwsConfigAlgorithm' => 'PS384',
            'expectedJwsConfigJwkKeys' => ['alg', 'use', 'kty', 'n', 'e', 'd', 'p', 'q', 'dp', 'dq', 'qi'],
            'expectedSerializedJweConfig' => 'Tjs=',
            'expectedAudience' => null,
            'expectedOutputJwkKeys' => ['alg', 'use', 'kty', 'n', 'e'],
            'defaultTtl' => self::DEFAULT_TTL,
        ];

        yield 'Only JWS: PS512' => [
            'input' => [
                'jws' => [
                    'alg' => 'PS512',
                ],
            ],
            'expectedTtl' => self::DEFAULT_TTL,
            'expectedClaims' => null,
            'expectedJwsConfigAlgorithm' => 'PS512',
            'expectedJwsConfigJwkKeys' => ['alg', 'use', 'kty', 'n', 'e', 'd', 'p', 'q', 'dp', 'dq', 'qi'],
            'expectedSerializedJweConfig' => 'Tjs=',
            'expectedAudience' => null,
            'expectedOutputJwkKeys' => ['alg', 'use', 'kty', 'n', 'e'],
            'defaultTtl' => self::DEFAULT_TTL,
        ];

        yield 'Only JWS: HS256' => [
            'input' => [
                'jws' => [
                    'alg' => 'HS256',
                ],
            ],
            'expectedTtl' => self::DEFAULT_TTL,
            'expectedClaims' => null,
            'expectedJwsConfigAlgorithm' => 'HS256',
            'expectedJwsConfigJwkKeys' => ['alg', 'use', 'kty', 'k'],
            'expectedSerializedJweConfig' => 'Tjs=',
            'expectedAudience' => null,
            'expectedOutputJwkKeys' => ['alg', 'use', 'kty', 'k'],
            'defaultTtl' => self::DEFAULT_TTL,
        ];

        yield 'Only JWS: HS384' => [
            'input' => [
                'jws' => [
                    'alg' => 'HS384',
                ],
            ],
            'expectedTtl' => self::DEFAULT_TTL,
            'expectedClaims' => null,
            'expectedJwsConfigAlgorithm' => 'HS384',
            'expectedJwsConfigJwkKeys' => ['alg', 'use', 'kty', 'k'],
            'expectedSerializedJweConfig' => 'Tjs=',
            'expectedAudience' => null,
            'expectedOutputJwkKeys' => ['alg', 'use', 'kty', 'k'],
            'defaultTtl' => self::DEFAULT_TTL,
        ];

        yield 'Only JWS: HS512' => [
            'input' => [
                'jws' => [
                    'alg' => 'HS512',
                ],
            ],
            'expectedTtl' => self::DEFAULT_TTL,
            'expectedClaims' => null,
            'expectedJwsConfigAlgorithm' => 'HS512',
            'expectedJwsConfigJwkKeys' => ['alg', 'use', 'kty', 'k'],
            'expectedSerializedJweConfig' => 'Tjs=',
            'expectedAudience' => null,
            'expectedOutputJwkKeys' => ['alg', 'use', 'kty', 'k'],
            'defaultTtl' => self::DEFAULT_TTL,
        ];
    }

    /**
     * @dataProvider invalidCreateProvider
     */
    public function testInvalidCreate(array $input, string $expectedMessage): void
    {
        $factory = new JwtTokenizerConfigFactory(self::DEFAULT_TTL, self::$algorithmManagerFactory, self::$compressionMethodManagerFactory);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage($expectedMessage);

        $factory->create($input);
    }

    public function invalidCreateProvider(): \Generator
    {
        yield 'TTL not a number' => [
            'input' => [
                'ttl' => true,
                'jws' => [
                    'alg' => 'ES256',
                ],
            ],
            'expectedMessage' => 'The "token.ttl" parameter must be a positive number or zero.',
        ];

        yield 'Negative TTL' => [
            'input' => [
                'ttl' => -600,
                'jws' => [
                    'alg' => 'ES256',
                ],
            ],
            'expectedMessage' => 'The "token.ttl" parameter must be a positive number or zero.',
        ];

        yield 'Claims not an array' => [
            'input' => [
                'claims' => 'username,email',
                'jws' => [
                    'alg' => 'ES256',
                ],
            ],
            'expectedMessage' => 'The "token.claims" parameter must be an array.',
        ];

        yield 'Claims with not a string value' => [
            'input' => [
                'claims' => ['username', ['email']],
                'jws' => [
                    'alg' => 'ES256',
                ],
            ],
            'expectedMessage' => 'The "token.claims" parameter must only contain a list of strings.',
        ];

        yield 'No JWS' => [
            'input' => [],
            'expectedMessage' => 'The "token.jws" parameter is required.',
        ];

        yield 'JWS is not an array' => [
            'input' => [
                'jws' => 'ES256',
            ],
            'expectedMessage' => 'The "token.jws" parameter must be an array.',
        ];

        yield 'JWS has no alg' => [
            'input' => [
                'jws' => [],
            ],
            'expectedMessage' => 'The "token.jws.alg" parameter is required.',
        ];

        yield 'JWS has invalid bits type' => [
            'input' => [
                'jws' => [
                    'alg' => 'RS256',
                    'bits' => '+inf',
                ],
            ],
            'expectedMessage' => 'The "token.jws.bits" parameter must be a number greater than or equal to 512.',
        ];

        yield 'JWS has invalid bits for RSA' => [
            'input' => [
                'jws' => [
                    'alg' => 'RS256',
                    'bits' => 511,
                ],
            ],
            'expectedMessage' => 'The "token.jws.bits" parameter must be a number greater than or equal to 512.',
        ];

        yield 'JWS has invalid bits for RSA (PS)' => [
            'input' => [
                'jws' => [
                    'alg' => 'PS256',
                    'bits' => 511,
                ],
            ],
            'expectedMessage' => 'The "token.jws.bits" parameter must be a number greater than or equal to 512.',
        ];

        yield 'JWS has invalid bits for oct' => [
            'input' => [
                'jws' => [
                    'alg' => 'HS384',
                    'bits' => 383,
                ],
            ],
            'expectedMessage' => 'The "token.jws.bits" parameter must be a number greater than or equal to 384.',
        ];

        yield 'JWS has invalid alg type' => [
            'input' => [
                'jws' => [
                    'alg' => 256,
                ],
            ],
            'expectedMessage' => 'The "token.jws.alg" parameter must be a string.',
        ];

        yield 'JWS has invalid alg' => [
            'input' => [
                'jws' => [
                    'alg' => 'RS128',
                ],
            ],
            'expectedMessage' => 'The "token.jws.alg" parameter contains unsupported algorithm.',
        ];

        yield 'JWE invalid type' => [
            'input' => [
                'jws' => [
                    'alg' => 'ES256',
                ],
                'jwe' => '...',
            ],
            'expectedMessage' => 'The "token.jwe" parameter must be an array.',
        ];

        yield 'JWE has no alg' => [
            'input' => [
                'jws' => [
                    'alg' => 'ES256',
                ],
                'jwe' => [
                    'enc' => 'A128GCM',
                    'jwk' => [],
                ],
            ],
            'expectedMessage' => 'The "token.jwe.alg" parameter is required.',
        ];

        yield 'JWE has no enc' => [
            'input' => [
                'jws' => [
                    'alg' => 'ES256',
                ],
                'jwe' => [
                    'alg' => 'RSA-OAEP',
                    'jwk' => [],
                ],
            ],
            'expectedMessage' => 'The "token.jwe.enc" parameter is required.',
        ];

        yield 'JWE has no jwk' => [
            'input' => [
                'jws' => [
                    'alg' => 'ES256',
                ],
                'jwe' => [
                    'alg' => 'RSA-OAEP',
                    'enc' => 'A128GCM',
                ],
            ],
            'expectedMessage' => 'The "token.jwe.jwk" parameter is required.',
        ];

        yield 'JWE has invalid alg type' => [
            'input' => [
                'jws' => [
                    'alg' => 'ES256',
                ],
                'jwe' => [
                    'alg' => ['RSA-OAEP'],
                    'enc' => 'A128GCM',
                    'jwk' => [],
                ],
            ],
            'expectedMessage' => 'The "token.jwe.alg" parameter must be a string.',
        ];

        yield 'JWE has invalid alg' => [
            'input' => [
                'jws' => [
                    'alg' => 'ES256',
                ],
                'jwe' => [
                    'alg' => 'RSA-OAEP-64',
                    'enc' => 'A128GCM',
                    'jwk' => [],
                ],
            ],
            'expectedMessage' => 'The "token.jwe.alg" parameter contains unsupported algorithm.',
        ];

        yield 'JWE has invalid enc type' => [
            'input' => [
                'jws' => [
                    'alg' => 'ES256',
                ],
                'jwe' => [
                    'alg' => 'RSA-OAEP',
                    'enc' => ['A128GCM'],
                    'jwk' => [],
                ],
            ],
            'expectedMessage' => 'The "token.jwe.enc" parameter must be a string.',
        ];

        yield 'JWE has invalid enc' => [
            'input' => [
                'jws' => [
                    'alg' => 'ES256',
                ],
                'jwe' => [
                    'alg' => 'RSA-OAEP',
                    'enc' => 'A1024GCM',
                    'jwk' => [],
                ],
            ],
            'expectedMessage' => 'The "token.jwe.enc" parameter contains unsupported algorithm.',
        ];

        yield 'JWE has invalid zip' => [
            'input' => [
                'jws' => [
                    'alg' => 'ES256',
                ],
                'jwe' => [
                    'alg' => 'RSA-OAEP',
                    'enc' => 'A128GCM',
                    'zip' => 'BR',
                    'jwk' => [],
                ],
            ],
            'expectedMessage' => 'The "token.jwe.zip" parameter contains unsupported compression method.',
        ];

        yield 'JWE jwk is not an array' => [
            'input' => [
                'jws' => [
                    'alg' => 'ES256',
                ],
                'jwe' => [
                    'alg' => 'RSA-OAEP',
                    'enc' => 'A128GCM',
                    'jwk' => 'MK',
                ],
            ],
            'expectedMessage' => 'The "token.jwe.jwk" parameter must be an array.',
        ];

        yield 'JWE has invalid jwk' => [
            'input' => [
                'jws' => [
                    'alg' => 'ES256',
                ],
                'jwe' => [
                    'alg' => 'RSA-OAEP',
                    'enc' => 'A128GCM',
                    'jwk' => [],
                ],
            ],
            'expectedMessage' => 'The "token.jwe.jwk" parameter has invalid value: The parameter "kty" is mandatory.',
        ];

        yield 'Audience invalid type' => [
            'input' => [
                'jws' => [
                    'alg' => 'ES256',
                ],
                'aud' => true,
            ],
            'expectedMessage' => 'The "token.aud" parameter must be a string.',
        ];

        yield 'Multiple errors' => [
            'input' => [
                'ttl' => null,
                'jwe' => [
                    'alg' => 'RSA-OAEP-64',
                    'enc' => 1,
                    'jwk' => ['kty' => 'RSA', 'n' => '...', 'e' => '...'],
                ],
            ],
            'expectedMessage' => implode("\n", [
                'The "token.ttl" parameter must be a positive number or zero.',
                'The "token.jws" parameter is required.',
                'The "token.jwe.alg" parameter contains unsupported algorithm.',
                'The "token.jwe.enc" parameter must be a string.',
            ]),
        ];
    }
}
