<?php declare(strict_types=1);
// SPDX-License-Identifier: BSD-3-Clause

namespace SingleA\Bundles\Singlea\Tests\Service\Marshaller;

use PHPUnit\Framework\TestCase;
use SingleA\Bundles\Singlea\FeatureConfig\Signature\SignatureConfig;
use SingleA\Bundles\Singlea\FeatureConfig\Signature\SignatureConfigInterface;
use SingleA\Bundles\Singlea\Service\Marshaller\FeatureConfigMarshallerFactory;
use SingleA\Bundles\Singlea\Tests\Service\TestFetcherConfig;
use SingleA\Bundles\Singlea\Tests\Service\TestTokenizerConfig;
use SingleA\Contracts\FeatureConfig\FeatureConfigInterface;
use SingleA\Contracts\PayloadFetcher\FetcherConfigInterface;
use SingleA\Contracts\Tokenization\TokenizerConfigInterface;

/**
 * @covers \SingleA\Bundles\Singlea\Service\Marshaller\FeatureConfigMarshallerFactory
 *
 * @internal
 */
final class FeatureConfigMarshallerFactoryTest extends TestCase
{
    /**
     * @dataProvider invalidInvokeProvider
     */
    public function testInvalidInvoke(string $interface, string $expectedMessage): void
    {
        $factory = new FeatureConfigMarshallerFactory();

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage($expectedMessage);

        $factory($interface);
    }

    public function invalidInvokeProvider(): \Generator
    {
        yield 'Unknown' => [
            'interface' => 'Unknown\\ConfigInterface',
            'expectedMessage' => 'An interface Unknown\\ConfigInterface does not exists.',
        ];

        yield 'Unexpected interface' => [
            'interface' => 'SingleA\\Contracts\\FeatureConfig\\FeatureConfigInterface',
            'expectedMessage' => 'Feature config marshaller can be initialized by an interface extends SingleA\Contracts\FeatureConfig\FeatureConfigInterface only.',
        ];
    }

    /**
     * @dataProvider supportsProvider
     */
    public function testSupports(string $interface, FeatureConfigInterface|string $config, bool $expected): void
    {
        $factory = new FeatureConfigMarshallerFactory();
        $marshaller = $factory($interface);

        self::assertSame($expected, $marshaller->supports($config));
    }

    public function supportsProvider(): \Generator
    {
        yield 'Supports (string)' => [
            'interface' => SignatureConfigInterface::class,
            'config' => SignatureConfig::class,
            'expected' => true,
        ];

        yield 'Supports (object)' => [
            'interface' => SignatureConfigInterface::class,
            'config' => new SignatureConfig(\OPENSSL_ALGO_SHA256, '', 0),
            'expected' => true,
        ];

        yield 'Not supports (string)' => [
            'interface' => SignatureConfigInterface::class,
            'config' => \stdClass::class,
            'expected' => false,
        ];

        yield 'Not supports (object)' => [
            'interface' => SignatureConfigInterface::class,
            'config' => $this->createStub(FeatureConfigInterface::class),
            'expected' => false,
        ];
    }

    /**
     * @dataProvider successfulMarshallProvider
     */
    public function testSuccessfulMarshall(
        bool $useIgbinarySerialize,
        string $interface,
        FeatureConfigInterface $config,
        string $expected,
    ): void {
        $factory = new FeatureConfigMarshallerFactory($useIgbinarySerialize);
        $marshaller = $factory($interface);

        self::assertSame($expected, base64_encode($marshaller->marshall($config)));
    }

    public function successfulMarshallProvider(): \Generator
    {
        yield 'Basic' => [
            'useIgbinarySerialize' => false,
            'interface' => TokenizerConfigInterface::class,
            'config' => new TestTokenizerConfig(600, ['foo', 'bar']),
            'expected' => 'Tzo1NzoiU2luZ2xlQVxCdW5kbGVzXFNpbmdsZWFcVGVzdHNcU2VydmljZVxUZXN0VG9rZW5pemVyQ29uZmlnIjoyOntpOjA7aTo2MDA7aToxO2E6Mjp7aTowO3M6MzoiZm9vIjtpOjE7czozOiJiYXIiO319',
        ];

        yield 'Igbinary' => [
            'useIgbinarySerialize' => true,
            'interface' => TokenizerConfigInterface::class,
            'config' => new TestTokenizerConfig(600, ['foo', 'bar']),
            'expected' => 'AAAAAhc5U2luZ2xlQVxCdW5kbGVzXFNpbmdsZWFcVGVzdHNcU2VydmljZVxUZXN0VG9rZW5pemVyQ29uZmlnFAIGAAgCWAYBFAIGABEDZm9vBgERA2Jhcg==',
        ];
    }

    public function testFailedMarshall(): void
    {
        $factory = new FeatureConfigMarshallerFactory(false);
        $marshaller = $factory(FetcherConfigInterface::class);

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Marshaller supports only SingleA\Contracts\PayloadFetcher\FetcherConfigInterface objects, SingleA\Bundles\Singlea\Tests\Service\TestTokenizerConfig passed.');

        $marshaller->marshall(new TestTokenizerConfig(null, null));
    }

    /**
     * @dataProvider successfulUnmarshallProvider
     */
    public function testSuccessfulUnmarshall(
        bool $useIgbinarySerialize,
        string $interface,
        string $value,
        string $expectedEndpoint,
        ?array $expectedClaims,
        ?array $expectedRequestOptions,
    ): void {
        $factory = new FeatureConfigMarshallerFactory($useIgbinarySerialize);
        $marshaller = $factory($interface);

        $config = $marshaller->unmarshall($value);

        self::assertInstanceOf(TestFetcherConfig::class, $config);
        self::assertSame($expectedEndpoint, $config->getEndpoint());
        self::assertSame($expectedClaims, $config->getClaims());
        self::assertSame($expectedRequestOptions, $config->getRequestOptions());
    }

    public function successfulUnmarshallProvider(): \Generator
    {
        yield 'Basic' => [
            'useIgbinarySerialize' => false,
            'interface' => FetcherConfigInterface::class,
            'value' => base64_decode('Tzo1NToiU2luZ2xlQVxCdW5kbGVzXFNpbmdsZWFcVGVzdHNcU2VydmljZVxUZXN0RmV0Y2hlckNvbmZpZyI6Mzp7aTowO3M6MjE6Imh0dHBzOi8vZW5kcG9pbnQudGVzdCI7aToxO2E6Mjp7aTowO3M6MzoiYmFyIjtpOjE7czozOiJmb28iO31pOjI7YToxOntzOjc6InRpbWVvdXQiO2k6MTU7fX0=', true),
            'expectedEndpoint' => 'https://endpoint.test',
            'expectedClaims' => ['bar', 'foo'],
            'expectedRequestOptions' => ['timeout' => 15],
        ];

        yield 'Igbinary' => [
            'useIgbinarySerialize' => true,
            'interface' => FetcherConfigInterface::class,
            'value' => base64_decode('AAAAAhc3U2luZ2xlQVxCdW5kbGVzXFNpbmdsZWFcVGVzdHNcU2VydmljZVxUZXN0RmV0Y2hlckNvbmZpZxQDBgARFGh0dHA6Ly9lbmRwb2ludC50ZXN0BgEABgIUAREHdGltZW91dAYZ', true),
            'expectedEndpoint' => 'http://endpoint.test',
            'expectedClaims' => null,
            'expectedRequestOptions' => ['timeout' => 25],
        ];
    }

    public function testFailedUnmarshall(): void
    {
        $factory = new FeatureConfigMarshallerFactory(false);
        $marshaller = $factory(FetcherConfigInterface::class);

        $value = base64_decode('Tzo1NzoiU2luZ2xlQVxCdW5kbGVzXFNpbmdsZWFcVGVzdHNcU2VydmljZVxUZXN0VG9rZW5pemVyQ29uZmlnIjoyOntpOjA7aTo2MDA7aToxO2E6Mjp7aTowO3M6MzoiZm9vIjtpOjE7czozOiJiYXIiO319', true);

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Unmarshalled value must be an instance of SingleA\Contracts\PayloadFetcher\FetcherConfigInterface, SingleA\Bundles\Singlea\Tests\Service\TestTokenizerConfig found.');

        $marshaller->unmarshall($value);
    }

    /**
     * @testWith [false, "TzoxNDoiVW5rbm93blxDb25maWciOjA6e30="]
     *           [true, "AAAAAhcOVW5rbm93blxDb25maWcUAA=="]
     */
    public function testUnmarshallUnknownClass(bool $useIgbinarySerialize, string $value): void
    {
        $factory = new FeatureConfigMarshallerFactory($useIgbinarySerialize);
        $marshaller = $factory(TokenizerConfigInterface::class);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Class Unknown\\Config not found. Maybe you forgot to require necessary feature package?');

        $marshaller->unmarshall(base64_decode($value, true));
    }
}
