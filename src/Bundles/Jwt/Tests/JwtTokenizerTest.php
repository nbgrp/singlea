<?php
// SPDX-License-Identifier: BSD-3-Clause

declare(strict_types=1);

namespace SingleA\Bundles\Jwt\Tests;

use Jose\Bundle\JoseFramework\Services\JWEBuilderFactory;
use Jose\Bundle\JoseFramework\Services\JWSBuilderFactory;
use Jose\Bundle\JoseFramework\Services\NestedTokenBuilderFactory;
use Jose\Component\Core\AlgorithmManagerFactory;
use Jose\Component\Core\JWK;
use Jose\Component\Encryption;
use Jose\Component\Signature;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use SingleA\Bundles\Jwt\FeatureConfig\JweConfig;
use SingleA\Bundles\Jwt\FeatureConfig\JwsConfig;
use SingleA\Bundles\Jwt\FeatureConfig\JwtTokenizerConfig;
use SingleA\Bundles\Jwt\JwtTokenizer;
use SingleA\Contracts\Tokenization\TokenizerConfigInterface;

/**
 * @covers \SingleA\Bundles\Jwt\JwtTokenizer
 *
 * @internal
 */
final class JwtTokenizerTest extends TestCase
{
    private static JWSBuilderFactory $jwsBuilderFactory;
    private static NestedTokenBuilderFactory $nestedTokenBuilderFactory;

    public static function setUpBeforeClass(): void
    {
        $algorithmManagerFactory = new AlgorithmManagerFactory();
        $algorithmManagerFactory->add('HS256', new Signature\Algorithm\HS256());
        $algorithmManagerFactory->add('A128KW', new Encryption\Algorithm\KeyEncryption\A128KW());
        $algorithmManagerFactory->add('A128GCM', new Encryption\Algorithm\ContentEncryption\A128GCM());

        $compressionMethodManagerFactory = new Encryption\Compression\CompressionMethodManagerFactory();
        $compressionMethodManagerFactory->add('DEF', new Encryption\Compression\Deflate());

        $jweSerializerManagerFactory = new Encryption\Serializer\JWESerializerManagerFactory();
        $jweSerializerManagerFactory->add(new Encryption\Serializer\CompactSerializer());

        $jwsSerializerManagerFactory = new Signature\Serializer\JWSSerializerManagerFactory();
        $jwsSerializerManagerFactory->add(new Signature\Serializer\CompactSerializer());

        $eventDispatcherStub = new class() implements EventDispatcherInterface {
            public function dispatch(object $event): void {}
        };

        self::$jwsBuilderFactory = new JWSBuilderFactory($algorithmManagerFactory, $eventDispatcherStub);

        self::$nestedTokenBuilderFactory = new NestedTokenBuilderFactory(
            new JWEBuilderFactory($algorithmManagerFactory, $compressionMethodManagerFactory, $eventDispatcherStub),
            $jweSerializerManagerFactory,
            self::$jwsBuilderFactory,
            $jwsSerializerManagerFactory,
            $eventDispatcherStub,
        );
    }

    /**
     * @dataProvider provideSupportsCases
     */
    public function testSupports(string|TokenizerConfigInterface $config, bool $expected): void
    {
        $tokenizer = new JwtTokenizer('iss-value', self::$jwsBuilderFactory, self::$nestedTokenBuilderFactory);

        self::assertSame($expected, $tokenizer->supports($config));
    }

    public function provideSupportsCases(): iterable
    {
        yield 'Wrong config' => [
            'config' => 'SingleA\Contracts\Tokenization\TokenizerConfigInterface',
            'expected' => false,
        ];

        yield 'Object config' => [
            'config' => new JwtTokenizerConfig(null, null, new JwsConfig('', new JWK(['kty' => '...'])), null, null),
            'expected' => true,
        ];

        yield 'String config' => [
            'config' => 'SingleA\Bundles\Jwt\FeatureConfig\JwtTokenizerConfig',
            'expected' => true,
        ];
    }

    public function testWrongConfigTokenize(): void
    {
        $tokenizer = new JwtTokenizer('iss-value', self::$jwsBuilderFactory, self::$nestedTokenBuilderFactory);
        $config = $this->createStub(TokenizerConfigInterface::class);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported config specified.');

        $tokenizer->tokenize('', [], $config);
    }

    /**
     * @dataProvider provideSuccessTokenizeCases
     */
    public function testSuccessTokenize(string $issuer, string $subject, array $payload, JwtTokenizerConfig $config, string $expected): void
    {
        $tokenizer = new JwtTokenizer($issuer, self::$jwsBuilderFactory, self::$nestedTokenBuilderFactory);

        self::assertStringStartsWith($expected, $tokenizer->tokenize($subject, $payload, $config));
    }

    public function provideSuccessTokenizeCases(): iterable
    {
        $payload = [
            'iat' => 1643490751,
            'nbf' => 1643490751,
            'exp' => 1643490171,
            'email' => 'ex@mple.me',
            'address' => 'St. Petersburg, Russia',
        ];

        $jwsConfig = new JwsConfig(
            'HS256',
            new JWK([
                'use' => 'sig',
                'alg' => 'HS256',
                'kty' => 'oct',
                'k' => 'DRxPpZHzhWiM5wf5IGtdlhGYoAx2Htpd8PN-snl6rAYjlsmfUZMofYyd5YaoLWHatMsuX7bQYL4_fYlXcYnB_w',
            ]),
        );

        $jweConfig = new JweConfig(
            'A128KW',
            'A128GCM',
            'DEF',
            new JWK([
                'use' => 'enc',
                'alg' => 'A128KW',
                'kty' => 'oct',
                'k' => 'BwzNuGzIxmKl4nqStr9oaiK6yAH57F2QJPy35Bl3QD1V8lBt-oQ0iKdjo0W_-Osvb8T7WCGDTSU3R_MKTn1EPA',
            ]),
        );

        yield 'TTL, no issuer, audience, no JWE' => [
            'issuer' => 'singlea',
            'subject' => 'tester',
            'payload' => $payload,
            'config' => new JwtTokenizerConfig(120, null, $jwsConfig, null, 'test-app'),
            'expected' => 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpYXQiOjE2NDM0OTA3NTEsIm5iZiI6MTY0MzQ5MDc1MSwiZXhwIjoxNjQzNDkwMTcxLCJlbWFpbCI6ImV4QG1wbGUubWUiLCJhZGRyZXNzIjoiU3QuIFBldGVyc2J1cmcsIFJ1c3NpYSIsInN1YiI6InRlc3RlciIsImlzcyI6InNpbmdsZWEiLCJhdWQiOiJ0ZXN0LWFwcCJ9.vwAr8I5AFwL2zyclS5JBXHLfZZpnSRvcpndFZHwbab4',
        ];

        yield 'No TTL, issuer, no audience, JWE (weak expected value)' => [
            'issuer' => 'singlea',
            'subject' => 'tester',
            'payload' => $payload,
            'config' => new JwtTokenizerConfig(null, null, $jwsConfig, $jweConfig, null),
            'expected' => 'eyJ',
        ];
    }
}
