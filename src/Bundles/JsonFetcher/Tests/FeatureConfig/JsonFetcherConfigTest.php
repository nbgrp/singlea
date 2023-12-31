<?php
// SPDX-License-Identifier: BSD-3-Clause

declare(strict_types=1);

namespace SingleA\Bundles\JsonFetcher\Tests\FeatureConfig;

use PHPUnit\Framework\TestCase;
use SingleA\Bundles\JsonFetcher\FeatureConfig\JsonFetcherConfig;

/**
 * @covers \SingleA\Bundles\JsonFetcher\FeatureConfig\JsonFetcherConfig
 *
 * @internal
 */
final class JsonFetcherConfigTest extends TestCase
{
    public function testConfig(): void
    {
        $config = new JsonFetcherConfig('https://endpoint.test', ['name'], ['timeout' => 10]);

        $serialized = serialize($config);
        self::assertSame(
            'Tzo1OToiU2luZ2xlQVxCdW5kbGVzXEpzb25GZXRjaGVyXEZlYXR1cmVDb25maWdcSnNvbkZldGNoZXJDb25maWciOjM6e3M6MToiZSI7czoyMToiaHR0cHM6Ly9lbmRwb2ludC50ZXN0IjtzOjE6ImMiO2E6MTp7aTowO3M6NDoibmFtZSI7fXM6MToibyI7YToxOntzOjc6InRpbWVvdXQiO2k6MTA7fX0=',
            base64_encode($serialized),
        );

        $config = unserialize($serialized);
        self::assertInstanceOf(JsonFetcherConfig::class, $config);
        self::assertSame('https://endpoint.test', $config->getEndpoint());
        self::assertSame(['name'], $config->getClaims());
        self::assertSame(['timeout' => 10], $config->getRequestOptions());
    }
}
