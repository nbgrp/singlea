<?php
// SPDX-License-Identifier: BSD-3-Clause

declare(strict_types=1);

namespace SingleA\Bundles\Jwt\Tests\FeatureConfig;

use Jose\Component\Core\JWK;
use PHPUnit\Framework\TestCase;
use SingleA\Bundles\Jwt\FeatureConfig\JweConfig;
use SingleA\Bundles\Jwt\FeatureConfig\JwsConfig;
use SingleA\Bundles\Jwt\FeatureConfig\JwtTokenizerConfig;

/**
 * @covers \SingleA\Bundles\Jwt\FeatureConfig\JweConfig
 * @covers \SingleA\Bundles\Jwt\FeatureConfig\JwsConfig
 * @covers \SingleA\Bundles\Jwt\FeatureConfig\JwtTokenizerConfig
 *
 * @internal
 */
final class JwtTokenizerConfigTest extends TestCase
{
    public function testInvalidTtl(): void
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Negative token TTL.');

        new JwtTokenizerConfig(-600, null, new JwsConfig('', new JWK(['kty' => '...'])), null, null);
    }

    public function testConfig(): void
    {
        $config = new JwtTokenizerConfig(
            660,
            ['name', 'email'],
            new JwsConfig(
                'ES256',
                new JWK([
                    'kty' => 'EC',
                    'crv' => 'P-256',
                    'x' => 'x-value',
                    'y' => 'y-value',
                ]),
            ),
            new JweConfig(
                'PBES2-HS512+A256KW',
                'A256CBC-HS512',
                'DEF',
                new JWK([
                    'kty' => 'oct',
                    'k' => 'k-value',
                ]),
            ),
            'test-app',
        );

        $serialized = serialize($config);
        self::assertSame(
            'Tzo1MjoiU2luZ2xlQVxCdW5kbGVzXEp3dFxGZWF0dXJlQ29uZmlnXEp3dFRva2VuaXplckNvbmZpZyI6NTp7czoxOiJ0IjtpOjY2MDtzOjE6ImMiO2E6Mjp7aTowO3M6NDoibmFtZSI7aToxO3M6NToiZW1haWwiO31zOjE6InMiO086NDM6IlNpbmdsZUFcQnVuZGxlc1xKd3RcRmVhdHVyZUNvbmZpZ1xKd3NDb25maWciOjI6e3M6MToiYSI7czo1OiJFUzI1NiI7czoxOiJrIjtPOjIzOiJKb3NlXENvbXBvbmVudFxDb3JlXEpXSyI6MTp7czozMToiAEpvc2VcQ29tcG9uZW50XENvcmVcSldLAHZhbHVlcyI7YTo0OntzOjM6Imt0eSI7czoyOiJFQyI7czozOiJjcnYiO3M6NToiUC0yNTYiO3M6MToieCI7czo3OiJ4LXZhbHVlIjtzOjE6InkiO3M6NzoieS12YWx1ZSI7fX19czoxOiJlIjtPOjQzOiJTaW5nbGVBXEJ1bmRsZXNcSnd0XEZlYXR1cmVDb25maWdcSndlQ29uZmlnIjo0OntzOjE6ImEiO3M6MTg6IlBCRVMyLUhTNTEyK0EyNTZLVyI7czoxOiJjIjtzOjEzOiJBMjU2Q0JDLUhTNTEyIjtzOjE6InoiO3M6MzoiREVGIjtzOjE6ImsiO086MjM6Ikpvc2VcQ29tcG9uZW50XENvcmVcSldLIjoxOntzOjMxOiIASm9zZVxDb21wb25lbnRcQ29yZVxKV0sAdmFsdWVzIjthOjI6e3M6Mzoia3R5IjtzOjM6Im9jdCI7czoxOiJrIjtzOjc6ImstdmFsdWUiO319fXM6MToiYSI7czo4OiJ0ZXN0LWFwcCI7fQ==',
            base64_encode($serialized),
        );

        $config = unserialize($serialized);
        self::assertInstanceOf(JwtTokenizerConfig::class, $config);
        self::assertSame(660, $config->getTtl());
        self::assertSame(['name', 'email'], $config->getClaims());
        self::assertSame('test-app', $config->getAudience());

        $jwsConfig = $config->getJwsConfig();
        self::assertSame('ES256', $jwsConfig->getAlgorithm());
        self::assertSame('pYVZv_YyMPqcss69GIei65J2mO3sXU8eTZW1zakm5o0', $jwsConfig->getJwk()->thumbprint('sha256'));

        $jweConfig = $config->getJweConfig();
        self::assertSame('PBES2-HS512+A256KW', $jweConfig->getKeyAlgorithm());
        self::assertSame('A256CBC-HS512', $jweConfig->getContentAlgorithm());
        self::assertSame('DEF', $jweConfig->getCompression());
        self::assertSame('VfU3wzDMEJYr-L6nWvGFZZZJXWlgg_irfmdmLr42zYE', $jweConfig->getRecipientJwk()->thumbprint('sha256'));
    }
}
