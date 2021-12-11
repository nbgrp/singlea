<?php declare(strict_types=1);
// SPDX-License-Identifier: BSD-3-Clause

namespace SingleA\Bundles\JwtFetcher\Tests;

use Jose\Bundle\JoseFramework\Services\JWEBuilderFactory;
use Jose\Bundle\JoseFramework\Services\JWEDecrypterFactory;
use Jose\Bundle\JoseFramework\Services\JWELoaderFactory;
use Jose\Bundle\JoseFramework\Services\JWSBuilderFactory;
use Jose\Bundle\JoseFramework\Services\JWSLoaderFactory;
use Jose\Bundle\JoseFramework\Services\JWSVerifierFactory;
use Jose\Bundle\JoseFramework\Services\NestedTokenBuilderFactory;
use Jose\Bundle\JoseFramework\Services\NestedTokenLoaderFactory;
use Jose\Component\Core\AlgorithmManagerFactory;
use Jose\Component\Core\JWK;
use Jose\Component\Encryption;
use Jose\Component\Signature;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use SingleA\Bundles\JwtFetcher\FeatureConfig\JweConfig;
use SingleA\Bundles\JwtFetcher\FeatureConfig\JwsConfig;
use SingleA\Bundles\JwtFetcher\FeatureConfig\JwtFetcherConfig;
use SingleA\Bundles\JwtFetcher\JwtFetcher;
use SingleA\Contracts\PayloadFetcher\FetcherConfigInterface;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

/**
 * @covers \SingleA\Bundles\JwtFetcher\JwtFetcher
 *
 * @internal
 */
final class JwtFetcherTest extends TestCase
{
    private static JWSBuilderFactory $jwsBuilderFactory;
    private static JWSLoaderFactory $jwsLoaderFactory;
    private static NestedTokenBuilderFactory $nestedTokenBuilderFactory;
    private static NestedTokenLoaderFactory $nestedTokenLoaderFactory;

    public static function setUpBeforeClass(): void
    {
        $algorithmManagerFactory = new AlgorithmManagerFactory();
        $algorithmManagerFactory->add('HS256', new Signature\Algorithm\HS256());
        $algorithmManagerFactory->add('ES256', new Signature\Algorithm\ES256());
        $algorithmManagerFactory->add('A128KW', new Encryption\Algorithm\KeyEncryption\A128KW());
        $algorithmManagerFactory->add('A128GCMKW', new Encryption\Algorithm\KeyEncryption\A128GCMKW());
        $algorithmManagerFactory->add('A128GCM', new Encryption\Algorithm\ContentEncryption\A128GCM());
        $algorithmManagerFactory->add('A128CBC-HS256', new Encryption\Algorithm\ContentEncryption\A128CBCHS256());

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
        self::$jwsLoaderFactory = new JWSLoaderFactory(
            $jwsSerializerManagerFactory,
            new JWSVerifierFactory($algorithmManagerFactory, $eventDispatcherStub),
            null,
            $eventDispatcherStub,
        );

        self::$nestedTokenLoaderFactory = new NestedTokenLoaderFactory(
            new JWELoaderFactory(
                $jweSerializerManagerFactory,
                new JWEDecrypterFactory($algorithmManagerFactory, $compressionMethodManagerFactory, $eventDispatcherStub),
                null,
                $eventDispatcherStub,
            ),
            self::$jwsLoaderFactory,
            $eventDispatcherStub,
        );
    }

    /**
     * @dataProvider supportsProvider
     */
    public function testSupports(FetcherConfigInterface|string $config, bool $expected): void
    {
        $fetcher = new JwtFetcher(
            new MockHttpClient(),
            self::$jwsBuilderFactory,
            self::$nestedTokenBuilderFactory,
            self::$jwsLoaderFactory,
            self::$nestedTokenLoaderFactory,
        );

        self::assertSame($expected, $fetcher->supports($config));
    }

    public function supportsProvider(): \Generator
    {
        yield 'Wrong config' => [
            'config' => 'SingleA\Contracts\PayloadFetcher\FetcherConfigInterface',
            'expected' => false,
        ];

        yield 'Object config' => [
            'config' => new JwtFetcherConfig(
                'https://endpoint.test',
                ['name'],
                new JwsConfig('RS256', new JWK(['kty' => 'RSA'])),
                new JweConfig('RSA-OAEP', 'A128GCM', null, new JWK(['kty' => 'RSA'])),
                ['timeout' => 10],
                new JwsConfig('ES256', new JWK(['kty' => 'EC'])),
                new JweConfig('A128KW', 'A192GCM', 'DEF', new JWK(['kty' => 'oct'])),
            ),
            'expected' => true,
        ];

        yield 'String config' => [
            'config' => 'SingleA\Bundles\JwtFetcher\FeatureConfig\JwtFetcherConfig',
            'expected' => true,
        ];
    }

    public function testFetchInvalidConfig(): void
    {
        $fetcher = new JwtFetcher(
            new MockHttpClient(),
            self::$jwsBuilderFactory,
            self::$nestedTokenBuilderFactory,
            self::$jwsLoaderFactory,
            self::$nestedTokenLoaderFactory,
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported config specified.');

        $fetcher->fetch([], $this->createStub(FetcherConfigInterface::class));
    }

    /**
     * @dataProvider fetchProvider
     */
    public function testFetch(
        array $requestData,
        string $responseBody,
        JwtFetcherConfig $config,
        int $expectedRequestBodyLength,
        array $expectedPayload,
        ?string $expectedRequestOptionsKey,
    ): void {
        $mockResponse = new MockResponse($responseBody);
        $httpClient = new MockHttpClient($mockResponse);

        $fetcher = new JwtFetcher(
            $httpClient,
            self::$jwsBuilderFactory,
            self::$nestedTokenBuilderFactory,
            self::$jwsLoaderFactory,
            self::$nestedTokenLoaderFactory,
        );
        $payload = $fetcher->fetch($requestData, $config);

        $requestOptions = $mockResponse->getRequestOptions();
        self::assertSame($expectedRequestBodyLength, \strlen($requestOptions['body']));
        self::assertSame('POST', $mockResponse->getRequestMethod());
        self::assertSame('https://endpoint.test/', $mockResponse->getRequestUrl());
        self::assertSame($expectedPayload, $payload);

        if ($expectedRequestOptionsKey) {
            self::assertArrayHasKey($expectedRequestOptionsKey, $requestOptions);
        }
    }

    public function fetchProvider(): \Generator
    {
        yield 'Request: JWS, no JWE; Response: JWS, no JWE' => [
            'requestData' => ['username' => 'tester'],
            'responseBody' => 'eyJhbGciOiJFUzI1NiIsInR5cCI6IkpXVCJ9.eyJjdXN0b21fZGF0YSI6NDJ9.cOvojLEEFob9leGouTybLLQqt36N-cKm_CM9o7DqVWgsa6TXnKkvMqAjtkpOFcblrXkWdWq4K1T31Y11GinttQ',
            'config' => new JwtFetcherConfig(
                'https://endpoint.test',
                null,
                new JwsConfig(
                    'HS256',
                    new JWK([
                        'use' => 'sig',
                        'alg' => 'HS256',
                        'kty' => 'oct',
                        'k' => 'DRxPpZHzhWiM5wf5IGtdlhGYoAx2Htpd8PN-snl6rAYjlsmfUZMofYyd5YaoLWHatMsuX7bQYL4_fYlXcYnB_w',
                    ]),
                ),
                null,
                null,
                new JwsConfig(
                    'ES256',
                    new JWK([
                        'use' => 'sig',
                        'alg' => 'ES256',
                        'kty' => 'EC',
                        'crv' => 'P-256',
                        'd' => 'jVN9umquzdRXru_53hXWnm7FZ_bVWIrgcghVmbO-ito',
                        'x' => 'rjjikDmq9fyhWn-T7SxsTNNMSfvMTtosHSPIxPzZH3E',
                        'y' => 'Nl6TpN7Fm593Gi4RI8e5LpRAXk_du_To-Qa9S-6S8qQ',
                    ]),
                ),
                null,
            ),
            'expectedRequestBodyLength' => 109,
            'expectedPayload' => ['custom_data' => 42],
            'expectedRequestOptionsKey' => null,
        ];

        yield 'Request: JWS, JWE (no zip); Response: JWS, JWE (with zip)' => [
            'requestData' => ['username' => 'tester'],
            'responseBody' => 'eyJpdiI6IkdPVlpMN2dXMFQ5Q0FjeE4iLCJ0YWciOiJ5eUFVU29BTE1wMDRpWldoVUFCalVBIiwiYWxnIjoiQTEyOEdDTUtXIiwiZW5jIjoiQTEyOENCQy1IUzI1NiIsInppcCI6IkRFRiIsImN0eSI6IkpXVCJ9.eDnrVvcmQEuPFBUefbs9fMZbKz4zmE_Nnx4DBzFOGDY.wo8niRIVFXWWK0LAEtZnMQ.7GLWa4SHELVNck45atRZ1EkQ64lcTjk_zI1T5xIMOdptMxUrJ2UY5MstAXSgx4WaTeS4k85IEKlSN4LICAgvB6MJpjkCDB3B488KP0FNpOAwE7AO9Ouh2Vp4zrjKG_kYQwNH6LtejltesxhHQImDVhNOQRtKfrpZhD6nepG3FVO6hkKwQwZXaKQnhnA8OUfK.OtqDjVSoI3G11TCvo6OTlg',
            'config' => new JwtFetcherConfig(
                'https://endpoint.test',
                null,
                new JwsConfig(
                    'HS256',
                    new JWK([
                        'use' => 'sig',
                        'alg' => 'HS256',
                        'kty' => 'oct',
                        'k' => 'DRxPpZHzhWiM5wf5IGtdlhGYoAx2Htpd8PN-snl6rAYjlsmfUZMofYyd5YaoLWHatMsuX7bQYL4_fYlXcYnB_w',
                    ]),
                ),
                new JweConfig(
                    'A128KW',
                    'A128GCM',
                    null,
                    new JWK([
                        'use' => 'enc',
                        'alg' => 'A128KW',
                        'kty' => 'oct',
                        'k' => 'mIGB0_qbHy_AET-Nn5iFz-RB1A4jEGj-79IaljvB4do',
                    ]),
                ),
                null,
                new JwsConfig(
                    'ES256',
                    new JWK([
                        'use' => 'sig',
                        'alg' => 'ES256',
                        'kty' => 'EC',
                        'crv' => 'P-256',
                        'd' => 'jVN9umquzdRXru_53hXWnm7FZ_bVWIrgcghVmbO-ito',
                        'x' => 'rjjikDmq9fyhWn-T7SxsTNNMSfvMTtosHSPIxPzZH3E',
                        'y' => 'Nl6TpN7Fm593Gi4RI8e5LpRAXk_du_To-Qa9S-6S8qQ',
                    ]),
                ),
                new JweConfig(
                    'A128GCMKW',
                    'A128CBC-HS256',
                    'DEF',
                    new JWK([
                        'use' => 'enc',
                        'alg' => 'A128GCMKW',
                        'kty' => 'oct',
                        'k' => 'BymaXaWg21hscfEauq0UEJXLkSqyG0zoDH7sYxJNIK4',
                    ]),
                ),
            ),
            'expectedRequestBodyLength' => 279,
            'expectedPayload' => ['custom_data' => 42],
            'expectedRequestOptionsKey' => null,
        ];

        yield 'Request: JWS, JWE (with zip); Response: JWS, JWE (no zip)' => [
            'requestData' => ['username' => 'tester'],
            'responseBody' => 'eyJpdiI6IlIwenBEbnhtd3FhVG9yM1giLCJ0YWciOiJ0Y2RObUM0elloY3VodlhPM3U1aGh3IiwiYWxnIjoiQTEyOEdDTUtXIiwiZW5jIjoiQTEyOENCQy1IUzI1NiIsImN0eSI6IkpXVCJ9.bsDiApmgXh-MoRP3aAGI2F4ZIxPUj2QU0kRit-p00ZM.pDSs1mMlNmSQIPHxWaG0xw.P-RowVxUxxVOuAIi2zNl5f5OcYodwW_ryU0ihP6QfZAzVeNbXLDTAOGAYsi1jF_8N6ggqa-dLtB_RPHUxkjR9P2NAgWOodvIy5jhBI0vBLFqHdGqhvbGv7H5nPblht0BaiSB9-qNcUPrUWSrDHQK4W_U_ybYBOQlzbU_4nwupjJhqUktuvcLdtreRfblUXc2_MlxO4ixTn7SXKnlKwhzBg.N1wSKbMkIS89msCVgbpPSw',
            'config' => new JwtFetcherConfig(
                'https://endpoint.test',
                null,
                new JwsConfig(
                    'HS256',
                    new JWK([
                        'use' => 'sig',
                        'alg' => 'HS256',
                        'kty' => 'oct',
                        'k' => 'DRxPpZHzhWiM5wf5IGtdlhGYoAx2Htpd8PN-snl6rAYjlsmfUZMofYyd5YaoLWHatMsuX7bQYL4_fYlXcYnB_w',
                    ]),
                ),
                new JweConfig(
                    'A128KW',
                    'A128GCM',
                    'DEF',
                    new JWK([
                        'use' => 'enc',
                        'alg' => 'A128KW',
                        'kty' => 'oct',
                        'k' => 'mIGB0_qbHy_AET-Nn5iFz-RB1A4jEGj-79IaljvB4do',
                    ]),
                ),
                null,
                new JwsConfig(
                    'ES256',
                    new JWK([
                        'use' => 'sig',
                        'alg' => 'ES256',
                        'kty' => 'EC',
                        'crv' => 'P-256',
                        'd' => 'jVN9umquzdRXru_53hXWnm7FZ_bVWIrgcghVmbO-ito',
                        'x' => 'rjjikDmq9fyhWn-T7SxsTNNMSfvMTtosHSPIxPzZH3E',
                        'y' => 'Nl6TpN7Fm593Gi4RI8e5LpRAXk_du_To-Qa9S-6S8qQ',
                    ]),
                ),
                new JweConfig(
                    'A128GCMKW',
                    'A128CBC-HS256',
                    null,
                    new JWK([
                        'use' => 'enc',
                        'alg' => 'A128GCMKW',
                        'kty' => 'oct',
                        'k' => 'BymaXaWg21hscfEauq0UEJXLkSqyG0zoDH7sYxJNIK4',
                    ]),
                ),
            ),
            'expectedRequestBodyLength' => 279,
            'expectedPayload' => ['custom_data' => 42],
            'expectedRequestOptionsKey' => null,
        ];

        yield 'Full set' => [
            'requestData' => ['username' => 'tester'],
            'responseBody' => 'eyJhbGciOiJBMTI4S1ciLCJlbmMiOiJBMTI4R0NNIiwiemlwIjoiREVGIiwiY3R5IjoiSldUIn0.yT_RSLW1Q3-MTIv7S0lzyYJLIIJoCU-o.2vOFViugBBUizpMi.IYvrtzLIQIKqexwC6jk_ig1nw3aufAZ0xCuftgaLy6aCPYN9rZH-61g4xtwnXwlYGotnhKHrOKakowyU3IJuKtmuk5jzR1aeNUsaxrl1OWNvLpaXcbvilWK4nY4NqVEgWs8Anx3p7SvgIS9z0mhz37F6JA.6kskCCqK-5auvS8oSHW1cg',
            'config' => new JwtFetcherConfig(
                'https://endpoint.test',
                null,
                new JwsConfig(
                    'ES256',
                    new JWK([
                        'use' => 'sig',
                        'alg' => 'ES256',
                        'kty' => 'EC',
                        'crv' => 'P-256',
                        'd' => 'jVN9umquzdRXru_53hXWnm7FZ_bVWIrgcghVmbO-ito',
                        'x' => 'rjjikDmq9fyhWn-T7SxsTNNMSfvMTtosHSPIxPzZH3E',
                        'y' => 'Nl6TpN7Fm593Gi4RI8e5LpRAXk_du_To-Qa9S-6S8qQ',
                    ]),
                ),
                new JweConfig(
                    'A128GCMKW',
                    'A128CBC-HS256',
                    'DEF',
                    new JWK([
                        'use' => 'enc',
                        'alg' => 'A128GCMKW',
                        'kty' => 'oct',
                        'k' => 'BymaXaWg21hscfEauq0UEJXLkSqyG0zoDH7sYxJNIK4',
                    ]),
                ),
                ['verify_peer' => false],
                new JwsConfig(
                    'HS256',
                    new JWK([
                        'use' => 'sig',
                        'alg' => 'HS256',
                        'kty' => 'oct',
                        'k' => 'DRxPpZHzhWiM5wf5IGtdlhGYoAx2Htpd8PN-snl6rAYjlsmfUZMofYyd5YaoLWHatMsuX7bQYL4_fYlXcYnB_w',
                    ]),
                ),
                new JweConfig(
                    'A128KW',
                    'A128GCM',
                    'DEF',
                    new JWK([
                        'use' => 'enc',
                        'alg' => 'A128KW',
                        'kty' => 'oct',
                        'k' => 'mIGB0_qbHy_AET-Nn5iFz-RB1A4jEGj-79IaljvB4do',
                    ]),
                ),
            ),
            'expectedRequestBodyLength' => 449,
            'expectedPayload' => ['custom_data' => 42],
            'expectedRequestOptionsKey' => 'verify_peer',
        ];
    }
}
