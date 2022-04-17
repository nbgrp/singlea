<?php declare(strict_types=1);
// SPDX-License-Identifier: BSD-3-Clause

namespace SingleA\Bundles\JwtFetcher;

use Jose\Bundle\JoseFramework\Services\JWSBuilderFactory;
use Jose\Bundle\JoseFramework\Services\JWSLoaderFactory;
use Jose\Bundle\JoseFramework\Services\NestedTokenBuilderFactory;
use Jose\Bundle\JoseFramework\Services\NestedTokenLoaderFactory;
use Jose\Component\Core\JWKSet;
use Jose\Component\Signature;
use SingleA\Bundles\JwtFetcher\FeatureConfig\JweConfig;
use SingleA\Bundles\JwtFetcher\FeatureConfig\JwtFetcherConfig;
use SingleA\Contracts\PayloadFetcher\FetcherConfigInterface;
use SingleA\Contracts\PayloadFetcher\FetcherInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class JwtFetcher implements FetcherInterface
{
    private Signature\Serializer\CompactSerializer $jwsSerializer;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly JWSBuilderFactory $jwsBuilderFactory,
        private readonly NestedTokenBuilderFactory $nestedTokenBuilderFactory,
        private readonly JWSLoaderFactory $jwsLoaderFactory,
        private readonly NestedTokenLoaderFactory $nestedTokenLoaderFactory,
    ) {
        $this->jwsSerializer = new Signature\Serializer\CompactSerializer();
    }

    public function supports(FetcherConfigInterface|string $config): bool
    {
        return is_a($config, JwtFetcherConfig::class, true);
    }

    /**
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     * @throws \JsonException
     */
    public function fetch(array $requestData, FetcherConfigInterface $config): array
    {
        if (!($config instanceof JwtFetcherConfig)) {
            throw new \InvalidArgumentException('Unsupported config specified.');
        }

        $options = $config->getRequestOptions() ?? [];
        $options['body'] = $config->getRequestJweConfig() === null
            ? $this->makeRequestJwt($requestData, $config)
            : $this->makeEncryptedRequestJwt($requestData, $config);

        $responseToken = $this->httpClient
            ->request('POST', $config->getEndpoint(), $options)
            ->getContent()
        ;

        $jws = $config->getResponseJweConfig() === null
            ? $this->parseResponseJwt($responseToken, $config)
            : $this->parseEncryptedResponseJwt($responseToken, $config);

        return (array) json_decode($jws->getPayload() ?? '[]', true, flags: \JSON_THROW_ON_ERROR);
    }

    private function makeRequestJwt(array $payload, JwtFetcherConfig $config): string
    {
        $jwsConfig = $config->getRequestJwsConfig();
        $jws = $this->jwsBuilderFactory->create([$jwsConfig->getAlgorithm()])
            ->withPayload(json_encode($payload, \JSON_THROW_ON_ERROR)) // @phan-suppress-current-line PhanPossiblyFalseTypeArgument
            ->addSignature($jwsConfig->getJwk(), [
                'alg' => $jwsConfig->getAlgorithm(),
                'typ' => 'JWT',
            ])
            ->build()
        ;

        return $this->jwsSerializer->serialize($jws);
    }

    private function makeEncryptedRequestJwt(array $payload, JwtFetcherConfig $config): string
    {
        $jwsConfig = $config->getRequestJwsConfig();
        $jweConfig = $config->getRequestJweConfig();

        if (!$jweConfig) {
            throw new \RuntimeException('Encrypted request JWT cannot be created.');
        }

        $builder = $this->nestedTokenBuilderFactory->create(
            ['jwe_compact'],
            [$jweConfig->getKeyAlgorithm()],
            [$jweConfig->getContentAlgorithm()],
            $jweConfig->getCompression() !== null ? [$jweConfig->getCompression()] : [],
            ['jws_compact'],
            [$jwsConfig->getAlgorithm()],
        );

        return $builder->create(
            json_encode($payload, \JSON_THROW_ON_ERROR), // @phan-suppress-current-line PhanPossiblyFalseTypeArgument
            [[
                'key' => $jwsConfig->getJwk(),
                'protected_header' => [
                    'alg' => $jwsConfig->getAlgorithm(),
                    'typ' => 'JWT',
                ],
            ]],
            'jws_compact',
            [
                'alg' => $jweConfig->getKeyAlgorithm(),
                'enc' => $jweConfig->getContentAlgorithm(),
            ],
            [],
            [[
                'key' => $jweConfig->getRecipientJwk(),
            ]],
            'jwe_compact',
        );
    }

    /**
     * @SuppressWarnings(PHPMD.UndefinedVariable)
     */
    private function parseResponseJwt(string $token, JwtFetcherConfig $config): Signature\JWS
    {
        return $this->jwsLoaderFactory
            ->create(
                ['jws_compact'],
                [$config->getResponseJwsConfig()->getAlgorithm()],
            )
            ->loadAndVerifyWithKey(
                $token,
                $config->getResponseJwsConfig()->getJwk(),
                $_,
            )
        ;
    }

    private function parseEncryptedResponseJwt(string $token, JwtFetcherConfig $config): Signature\JWS
    {
        $jwsConfig = $config->getResponseJwsConfig();
        $jweConfig = $config->getResponseJweConfig();

        if (!($jweConfig instanceof JweConfig)) {
            throw new \RuntimeException('Response JWT cannot be decrypted.');
        }

        return $this->nestedTokenLoaderFactory
            ->create(
                ['jwe_compact'],
                [$jweConfig->getKeyAlgorithm()],
                [$jweConfig->getContentAlgorithm()],
                ($jweConfig->getCompression() !== null ? [$jweConfig->getCompression()] : []),
                [],
                ['jws_compact'],
                [$jwsConfig->getAlgorithm()],
                [],
            )
            ->load(
                $token,
                new JWKSet([$jweConfig->getRecipientJwk()]),
                new JWKSet([$jwsConfig->getJwk()]),
            )
        ;
    }
}
