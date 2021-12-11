<?php declare(strict_types=1);
// SPDX-License-Identifier: BSD-3-Clause

namespace SingleA\Bundles\Singlea\Tests\Service\Marshaller;

use PHPUnit\Framework\TestCase;
use SingleA\Bundles\Singlea\Service\Marshaller\SodiumFeatureConfigEncryptor;

/**
 * @covers \SingleA\Bundles\Singlea\Service\Marshaller\SodiumFeatureConfigEncryptor
 *
 * @internal
 */
final class SodiumEncryptorTest extends TestCase
{
    /**
     * @dataProvider invalidKeysProvider
     */
    public function testInvalidKeys(mixed $keys, string $expectedMessage): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage($expectedMessage);

        new SodiumFeatureConfigEncryptor($keys);
    }

    public function invalidKeysProvider(): \Generator
    {
        yield 'Invalid keys type' => [
            'keys' => 'key',
            'expectedMessage' => 'Client keys must be provided as an array.',
        ];

        yield 'Empty keys' => [
            'keys' => [],
            'expectedMessage' => 'At least one key must be provided.',
        ];
    }

    public function testSuccessfulEncrypt(): void
    {
        $encryptor = new SodiumFeatureConfigEncryptor([
            base64_decode('nJoghQK1Mjc8mAyuhtnWqFjRBWXiXwvXMdOJqRfnx38=', true),
        ]);

        $value = 'marshalled-config';
        $secret = base64_decode('icvrQN9MUai5thrP8yGpmgTTqnD9gWU9', true);

        self::assertSame('DiAGduXLMAKyAphsLZgtgwtlDvxTxctaNijlTUflWxBy', base64_encode($encryptor->encrypt($value, $secret)));
    }

    public function testSuccessfulDecrypt(): void
    {
        $encryptor = new SodiumFeatureConfigEncryptor([
            base64_decode('nJoghQK1Mjc8mAyuhtnWqFjRBWXiXwvXMdOJqRfnx38=', true),
            base64_decode('9H0i6tem7uv65PKFItUzbX5JBXWEtm9vBs2gYZid7dI=', true),
        ]);

        $value = base64_decode('2eAkLgN3JhBcaUpM/FBgWMJctXsINfIpkzXG666hYQbB', true);
        $secret = base64_decode('2obmaFvCEpZzlOgVNF7Ovk23i2W/1Po+', true);

        self::assertSame('marshalled-config', $encryptor->decrypt($value, $secret));
    }

    public function testFailedDecrypt(): void
    {
        $encryptor = new SodiumFeatureConfigEncryptor([
            base64_decode('nJoghQK1Mjc8mAyuhtnWqFjRBWXiXwvXMdOJqRfnx38=', true),
        ]);

        $value = base64_decode('2eAkLgN3JhBcaUpM/FBgWMJctXsINfIpkzXG666hYQbB', true);
        $secret = base64_decode('2obmaFvCEpZzlOgVNF7Ovk23i2W/1Po+', true);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot decrypt value.');

        $encryptor->decrypt($value, $secret);
    }
}
