<?php
// SPDX-License-Identifier: BSD-3-Clause

declare(strict_types=1);

namespace SingleA\Bundles\Singlea\Tests\FeatureConfig\Signature;

use PHPUnit\Framework\TestCase;
use SingleA\Bundles\Singlea\FeatureConfig\Signature\SignatureConfigFactory;

/**
 * @covers \SingleA\Bundles\Singlea\FeatureConfig\Signature\SignatureConfigFactory
 *
 * @internal
 */
final class SignatureConfigFactoryTest extends TestCase
{
    public function testStrings(): void
    {
        $factory = new SignatureConfigFactory();

        self::assertSame('SingleA\Bundles\Singlea\FeatureConfig\Signature\SignatureConfig', $factory->getConfigClass());
        self::assertSame('signature', $factory->getKey());
        self::assertSame('signature', $factory->getHash());
    }

    /**
     * @dataProvider provideSuccessfulCreateCases
     */
    public function testSuccessfulCreate(
        array $input,
        int $expectedMessageDigestAlgorithm,
        string $expectedPublicKey,
        ?int $expectedClientClockSkew,
    ): void {
        $factory = new SignatureConfigFactory();
        $config = $factory->create($input);

        self::assertSame($expectedMessageDigestAlgorithm, $config->getMessageDigestAlgorithm());
        self::assertSame($expectedPublicKey, $config->getPublicKey());
        self::assertSame($expectedClientClockSkew, $config->getClientClockSkew());
    }

    public function provideSuccessfulCreateCases(): iterable
    {
        yield 'With client clock skew' => [
            'input' => [
                'md-alg' => 'sha384',
                'key' => "-----BEGIN PUBLIC KEY-----\nMIIBIjANB...",
                'skew' => -3600,
            ],
            'expectedMessageDigestAlgorithm' => \OPENSSL_ALGO_SHA384,
            'expectedPublicKey' => "-----BEGIN PUBLIC KEY-----\nMIIBIjANB...",
            'expectedClientClockSkew' => -3600,
        ];

        yield 'Without client clock skew' => [
            'input' => [
                'md-alg' => 'RMD160',
                'key' => "-----BEGIN PUBLIC KEY-----\nMIIBIjANC...",
            ],
            'expectedMessageDigestAlgorithm' => \OPENSSL_ALGO_RMD160,
            'expectedPublicKey' => "-----BEGIN PUBLIC KEY-----\nMIIBIjANC...",
            'expectedClientClockSkew' => 0,
        ];
    }

    /**
     * @dataProvider provideInvalidCreateCases
     */
    public function testInvalidCreate(array $input, string $expectedMessage): void
    {
        $factory = new SignatureConfigFactory();

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage($expectedMessage);

        $factory->create($input);
    }

    public function provideInvalidCreateCases(): iterable
    {
        yield 'Without "md-alg"' => [
            'input' => [
                'key' => '-----BEGIN PUBLIC KEY-----',
            ],
            'expectedMessage' => 'The "signature.md-alg" parameter is required.',
        ];

        yield 'Invalid "md-alg" type' => [
            'input' => [
                'md-alg' => 7,
                'key' => '-----BEGIN PUBLIC KEY-----',
            ],
            'expectedMessage' => 'The "signature.md-alg" parameter must be a string.',
        ];

        yield 'Invalid "md-alg" value' => [
            'input' => [
                'md-alg' => '7',
                'key' => '-----BEGIN PUBLIC KEY-----',
            ],
            'expectedMessage' => 'The "signature.md-alg" parameter is invalid, unknown constant OPENSSL_ALGO_7.',
        ];

        yield 'Without "key"' => [
            'input' => [
                'md-alg' => 'sha512',
            ],
            'expectedMessage' => 'The "signature.key" parameter is required.',
        ];

        yield 'Invalid "key" type' => [
            'input' => [
                'md-alg' => 'RMD160',
                'key' => false,
            ],
            'expectedMessage' => 'The "signature.key" parameter must be a string.',
        ];

        yield 'Invalid "skew" type' => [
            'input' => [
                'md-alg' => 'sha1',
                'key' => '-----BEGIN PUBLIC KEY-----',
                'skew' => '1 hour',
            ],
            'expectedMessage' => 'The "signature.skew" parameter must be a number.',
        ];

        yield 'Multiple error' => [
            'input' => [
                'md-alg' => 7,
                'skew' => null,
            ],
            'expectedMessage' => implode("\n", [
                'The "signature.md-alg" parameter must be a string.',
                'The "signature.key" parameter is required.',
                'The "signature.skew" parameter must be a number.',
            ]),
        ];
    }
}
