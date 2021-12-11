<?php declare(strict_types=1);
// SPDX-License-Identifier: BSD-3-Clause

namespace SingleA\Bundles\JsonFetcher\Tests;

use PHPUnit\Framework\TestCase;
use SingleA\Bundles\JsonFetcher\FeatureConfig\JsonFetcherConfig;
use SingleA\Bundles\JsonFetcher\JsonFetcher;
use SingleA\Contracts\PayloadFetcher\FetcherConfigInterface;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

/**
 * @covers \SingleA\Bundles\JsonFetcher\JsonFetcher
 *
 * @internal
 */
final class JsonFetcherTest extends TestCase
{
    /**
     * @dataProvider supportsProvider
     */
    public function testSupports(FetcherConfigInterface|string $config, bool $expected): void
    {
        $fetcher = new JsonFetcher(new MockHttpClient());

        self::assertSame($expected, $fetcher->supports($config));
    }

    public function supportsProvider(): \Generator
    {
        yield 'Wrong config' => [
            'config' => 'SingleA\Contracts\PayloadFetcher\FetcherConfigInterface',
            'expected' => false,
        ];

        yield 'Object config' => [
            'config' => new JsonFetcherConfig('https://endpoint.test', null, null),
            'expected' => true,
        ];

        yield 'String config' => [
            'config' => 'SingleA\Bundles\JsonFetcher\FeatureConfig\JsonFetcherConfig',
            'expected' => true,
        ];
    }

    public function testFetchInvalidConfig(): void
    {
        $fetcher = new JsonFetcher(new MockHttpClient());

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported config specified.');

        $fetcher->fetch([], $this->createStub(FetcherConfigInterface::class));
    }

    /**
     * @dataProvider fetchProvider
     */
    public function testFetch(array $requestData, string $responseBody, JsonFetcherConfig $config, array $expectedPayload, ?string $expectedRequestOptionsKey): void
    {
        $mockResponse = new MockResponse($responseBody);
        $httpClient = new MockHttpClient($mockResponse);

        $fetcher = new JsonFetcher($httpClient);
        $payload = $fetcher->fetch($requestData, $config);

        self::assertSame('POST', $mockResponse->getRequestMethod());
        self::assertSame('https://endpoint.test/', $mockResponse->getRequestUrl());
        self::assertSame($expectedPayload, $payload);

        if ($expectedRequestOptionsKey) {
            self::assertArrayHasKey($expectedRequestOptionsKey, $mockResponse->getRequestOptions());
        }
    }

    public function fetchProvider(): \Generator
    {
        yield 'Without options' => [
            'requestData' => ['username' => 'tester'],
            'responseBody' => '{"extra":"data"}',
            'config' => new JsonFetcherConfig('https://endpoint.test', null, null),
            'expectedPayload' => ['extra' => 'data'],
            'expectedRequestOptionsKey' => null,
        ];

        yield 'With options' => [
            'requestData' => ['username' => 'tester'],
            'responseBody' => '{"extra":"data"}',
            'config' => new JsonFetcherConfig('https://endpoint.test', null, ['verify_peer' => false]),
            'expectedPayload' => ['extra' => 'data'],
            'expectedRequestOptionsKey' => 'verify_peer',
        ];
    }
}
