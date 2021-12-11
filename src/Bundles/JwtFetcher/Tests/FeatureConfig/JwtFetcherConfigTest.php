<?php declare(strict_types=1);
// SPDX-License-Identifier: BSD-3-Clause

namespace SingleA\Bundles\JwtFetcher\Tests\FeatureConfig;

use Jose\Component\Core\JWK;
use PHPUnit\Framework\TestCase;
use SingleA\Bundles\JwtFetcher\FeatureConfig\JweConfig;
use SingleA\Bundles\JwtFetcher\FeatureConfig\JwsConfig;
use SingleA\Bundles\JwtFetcher\FeatureConfig\JwtFetcherConfig;

/**
 * @covers \SingleA\Bundles\JwtFetcher\FeatureConfig\JweConfig
 * @covers \SingleA\Bundles\JwtFetcher\FeatureConfig\JwsConfig
 * @covers \SingleA\Bundles\JwtFetcher\FeatureConfig\JwtFetcherConfig
 *
 * @internal
 */
final class JwtFetcherConfigTest extends TestCase
{
    public function testConfig(): void
    {
        $config = new JwtFetcherConfig(
            'https://endpoint.test',
            ['name'],
            new JwsConfig('RS256', new JWK(['kty' => 'RSA'])),
            new JweConfig('RSA-OAEP', 'A128GCM', null, new JWK(['kty' => 'RSA'])),
            ['timeout' => 10],
            new JwsConfig('ES256', new JWK(['kty' => 'EC'])),
            new JweConfig('A128KW', 'A192GCM', 'DEF', new JWK(['kty' => 'oct'])),
        );

        $serialized = serialize($config);
        self::assertSame(
            'Tzo1NzoiU2luZ2xlQVxCdW5kbGVzXEp3dEZldGNoZXJcRmVhdHVyZUNvbmZpZ1xKd3RGZXRjaGVyQ29uZmlnIjo3OntzOjE6ImUiO3M6MjE6Imh0dHBzOi8vZW5kcG9pbnQudGVzdCI7czoxOiJjIjthOjE6e2k6MDtzOjQ6Im5hbWUiO31zOjI6InFzIjtPOjUwOiJTaW5nbGVBXEJ1bmRsZXNcSnd0RmV0Y2hlclxGZWF0dXJlQ29uZmlnXEp3c0NvbmZpZyI6Mjp7czoxOiJhIjtzOjU6IlJTMjU2IjtzOjE6ImsiO086MjM6Ikpvc2VcQ29tcG9uZW50XENvcmVcSldLIjoxOntzOjMxOiIASm9zZVxDb21wb25lbnRcQ29yZVxKV0sAdmFsdWVzIjthOjE6e3M6Mzoia3R5IjtzOjM6IlJTQSI7fX19czoyOiJxZSI7Tzo1MDoiU2luZ2xlQVxCdW5kbGVzXEp3dEZldGNoZXJcRmVhdHVyZUNvbmZpZ1xKd2VDb25maWciOjQ6e3M6MToiYSI7czo4OiJSU0EtT0FFUCI7czoxOiJjIjtzOjc6IkExMjhHQ00iO3M6MToieiI7TjtzOjE6ImsiO086MjM6Ikpvc2VcQ29tcG9uZW50XENvcmVcSldLIjoxOntzOjMxOiIASm9zZVxDb21wb25lbnRcQ29yZVxKV0sAdmFsdWVzIjthOjE6e3M6Mzoia3R5IjtzOjM6IlJTQSI7fX19czoyOiJxbyI7YToxOntzOjc6InRpbWVvdXQiO2k6MTA7fXM6MjoicHMiO086NTA6IlNpbmdsZUFcQnVuZGxlc1xKd3RGZXRjaGVyXEZlYXR1cmVDb25maWdcSndzQ29uZmlnIjoyOntzOjE6ImEiO3M6NToiRVMyNTYiO3M6MToiayI7TzoyMzoiSm9zZVxDb21wb25lbnRcQ29yZVxKV0siOjE6e3M6MzE6IgBKb3NlXENvbXBvbmVudFxDb3JlXEpXSwB2YWx1ZXMiO2E6MTp7czozOiJrdHkiO3M6MjoiRUMiO319fXM6MjoicGUiO086NTA6IlNpbmdsZUFcQnVuZGxlc1xKd3RGZXRjaGVyXEZlYXR1cmVDb25maWdcSndlQ29uZmlnIjo0OntzOjE6ImEiO3M6NjoiQTEyOEtXIjtzOjE6ImMiO3M6NzoiQTE5MkdDTSI7czoxOiJ6IjtzOjM6IkRFRiI7czoxOiJrIjtPOjIzOiJKb3NlXENvbXBvbmVudFxDb3JlXEpXSyI6MTp7czozMToiAEpvc2VcQ29tcG9uZW50XENvcmVcSldLAHZhbHVlcyI7YToxOntzOjM6Imt0eSI7czozOiJvY3QiO319fX0=',
            base64_encode($serialized),
        );

        $config = unserialize($serialized);
        self::assertInstanceOf(JwtFetcherConfig::class, $config);
        self::assertSame('https://endpoint.test', $config->getEndpoint());
        self::assertSame(['name'], $config->getClaims());
        self::assertSame(['timeout' => 10], $config->getRequestOptions());

        $requestJwsConfig = $config->getRequestJwsConfig();
        self::assertSame('RS256', $requestJwsConfig->getAlgorithm());
        self::assertSame(['kty' => 'RSA'], $requestJwsConfig->getJwk()->all());

        $requestJweConfig = $config->getRequestJweConfig();
        self::assertSame('RSA-OAEP', $requestJweConfig->getKeyAlgorithm());
        self::assertSame('A128GCM', $requestJweConfig->getContentAlgorithm());
        self::assertNull($requestJweConfig->getCompression());
        self::assertSame(['kty' => 'RSA'], $requestJweConfig->getRecipientJwk()->all());

        $responseJwsConfig = $config->getResponseJwsConfig();
        self::assertSame('ES256', $responseJwsConfig->getAlgorithm());
        self::assertSame(['kty' => 'EC'], $responseJwsConfig->getJwk()->all());

        $responseJweConfig = $config->getResponseJweConfig();
        self::assertSame('A128KW', $responseJweConfig->getKeyAlgorithm());
        self::assertSame('A192GCM', $responseJweConfig->getContentAlgorithm());
        self::assertSame('DEF', $responseJweConfig->getCompression());
        self::assertSame(['kty' => 'oct'], $responseJweConfig->getRecipientJwk()->all());
    }
}
