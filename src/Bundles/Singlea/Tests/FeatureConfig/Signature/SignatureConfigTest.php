<?php declare(strict_types=1);
// SPDX-License-Identifier: BSD-3-Clause

namespace SingleA\Bundles\Singlea\Tests\FeatureConfig\Signature;

use PHPUnit\Framework\TestCase;
use SingleA\Bundles\Singlea\FeatureConfig\Signature\SignatureConfig;

/**
 * @covers \SingleA\Bundles\Singlea\FeatureConfig\Signature\SignatureConfig
 *
 * @internal
 */
final class SignatureConfigTest extends TestCase
{
    public function testConfig(): void
    {
        $config = new SignatureConfig(\OPENSSL_ALGO_SHA256, "-----BEGIN PUBLIC KEY-----\nMIIB...", 3600);

        $serialized = serialize($config);
        self::assertSame(
            'Tzo2MzoiU2luZ2xlQVxCdW5kbGVzXFNpbmdsZWFcRmVhdHVyZUNvbmZpZ1xTaWduYXR1cmVcU2lnbmF0dXJlQ29uZmlnIjozOntzOjE6ImEiO2k6NztzOjE6ImsiO3M6MzQ6Ii0tLS0tQkVHSU4gUFVCTElDIEtFWS0tLS0tCk1JSUIuLi4iO3M6MToicyI7aTozNjAwO30=',
            base64_encode($serialized),
        );

        $config = unserialize($serialized);
        self::assertInstanceOf(SignatureConfig::class, $config);
        self::assertSame(\OPENSSL_ALGO_SHA256, $config->getMessageDigestAlgorithm());
        self::assertSame("-----BEGIN PUBLIC KEY-----\nMIIB...", $config->getPublicKey());
        self::assertSame(3600, $config->getClientClockSkew());
    }
}
