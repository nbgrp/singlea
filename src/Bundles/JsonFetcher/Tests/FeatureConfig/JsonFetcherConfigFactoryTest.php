<?php
// SPDX-License-Identifier: BSD-3-Clause

declare(strict_types=1);

namespace SingleA\Bundles\JsonFetcher\Tests\FeatureConfig;

use PHPUnit\Framework\TestCase;
use SingleA\Bundles\JsonFetcher\FeatureConfig\JsonFetcherConfigFactory;

/**
 * @covers \SingleA\Bundles\JsonFetcher\FeatureConfig\JsonFetcherConfigFactory
 *
 * @internal
 */
final class JsonFetcherConfigFactoryTest extends TestCase
{
    public function testStrings(): void
    {
        $factory = new JsonFetcherConfigFactory(true);

        self::assertSame('SingleA\Bundles\JsonFetcher\FeatureConfig\JsonFetcherConfig', $factory->getConfigClass());
        self::assertSame('payload', $factory->getKey());
        self::assertSame('json', $factory->getHash());
    }

    /**
     * @dataProvider provideSuccessfulCreateCases
     */
    public function testSuccessfulCreate(
        bool $httpsOnly,
        array $input,
        string $expectedEndpoint,
        ?array $expectedClaims,
        ?array $expectedRequestOptions,
    ): void {
        $factory = new JsonFetcherConfigFactory($httpsOnly);
        $config = $factory->create($input);

        self::assertSame($expectedEndpoint, $config->getEndpoint());
        self::assertSame($expectedClaims, $config->getClaims());
        self::assertSame($expectedRequestOptions, $config->getRequestOptions());
    }

    public function provideSuccessfulCreateCases(): iterable
    {
        yield 'HTTP endpoint' => [
            'httpsOnly' => false,
            'input' => [
                'endpoint' => 'http://endpoint.test',
                'claims' => ['username', 'email'],
            ],
            'expectedEndpoint' => 'http://endpoint.test',
            'expectedClaims' => ['username', 'email'],
            'expectedRequestOptions' => null,
        ];

        yield 'HTTPS endpoint' => [
            'httpsOnly' => true,
            'input' => [
                'endpoint' => 'https://endpoint.test',
                'claims' => ['email', 'groups'],
                'options' => ['timeout' => 10],
            ],
            'expectedEndpoint' => 'https://endpoint.test',
            'expectedClaims' => ['email', 'groups'],
            'expectedRequestOptions' => ['timeout' => 10],
        ];

        yield 'Endpoint only' => [
            'httpsOnly' => true,
            'input' => [
                'endpoint' => 'https://endpoint.test',
            ],
            'expectedEndpoint' => 'https://endpoint.test',
            'expectedClaims' => null,
            'expectedRequestOptions' => null,
        ];
    }

    /**
     * @dataProvider provideInvalidCreateCases
     */
    public function testInvalidCreate(array $input, bool $httpsOnly, string $expectedMessage): void
    {
        $factory = new JsonFetcherConfigFactory($httpsOnly);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage($expectedMessage);

        $factory->create($input);
    }

    public function provideInvalidCreateCases(): iterable
    {
        yield 'Without "endpoint"' => [
            'input' => [],
            'httpsOnly' => true,
            'expectedMessage' => 'The "payload.endpoint" parameter is required.',
        ];

        yield 'Invalid "endpoint" type' => [
            'input' => [
                'endpoint' => false,
            ],
            'httpsOnly' => true,
            'expectedMessage' => 'The "payload.endpoint" parameter must be a string.',
        ];

        yield 'Invalid "endpoint" value' => [
            'input' => [
                'endpoint' => 'htt://invalid value',
            ],
            'httpsOnly' => false,
            'expectedMessage' => 'The "payload.endpoint" parameter must be an URL with http scheme.',
        ];

        yield 'HTTP endpoint' => [
            'input' => [
                'endpoint' => 'http://endpoint.test',
            ],
            'httpsOnly' => true,
            'expectedMessage' => 'The "payload.endpoint" parameter must be an URL with https scheme.',
        ];

        yield 'Invalid "claims" type' => [
            'input' => [
                'endpoint' => 'https://endpoint.test',
                'claims' => null,
            ],
            'httpsOnly' => true,
            'expectedMessage' => 'The "payload.claims" parameter must be an array.',
        ];

        yield 'Invalid "options" type' => [
            'input' => [
                'endpoint' => 'https://endpoint.test',
                'options' => 0,
            ],
            'httpsOnly' => true,
            'expectedMessage' => 'The "payload.options" parameter must be an array.',
        ];

        yield 'Multiple errors' => [
            'input' => [
                'endpoint' => 'ttp://e',
                'claims' => true,
                'options' => false,
            ],
            'httpsOnly' => true,
            'expectedMessage' => implode("\n", [
                'The "payload.endpoint" parameter must be an URL with https scheme.',
                'The "payload.claims" parameter must be an array.',
                'The "payload.options" parameter must be an array.',
            ]),
        ];
    }
}
