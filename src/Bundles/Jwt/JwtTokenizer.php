<?php declare(strict_types=1);
// SPDX-License-Identifier: BSD-3-Clause

namespace SingleA\Bundles\Jwt;

use Jose\Bundle\JoseFramework\Services\JWSBuilderFactory;
use Jose\Bundle\JoseFramework\Services\NestedTokenBuilderFactory;
use Jose\Component\Signature;
use SingleA\Bundles\Jwt\FeatureConfig\JwtTokenizerConfig;
use SingleA\Contracts\Tokenization\TokenizerConfigInterface;
use SingleA\Contracts\Tokenization\TokenizerInterface;

final class JwtTokenizer implements TokenizerInterface
{
    private Signature\Serializer\CompactSerializer $jwsSerializer;

    public function __construct(
        private ?string $issuer,
        private JWSBuilderFactory $jwsBuilderFactory,
        private NestedTokenBuilderFactory $nestedTokenBuilderFactory,
    ) {
        $this->jwsSerializer = new Signature\Serializer\CompactSerializer();
    }

    public function supports(TokenizerConfigInterface|string $config): bool
    {
        return is_a($config, JwtTokenizerConfig::class, true);
    }

    /**
     * @psalm-suppress MixedAssignment
     */
    public function tokenize(string $subject, array $payload, TokenizerConfigInterface $config): string
    {
        if (!($config instanceof JwtTokenizerConfig)) {
            throw new \InvalidArgumentException('Unsupported config specified.');
        }

        // Add standard JWT payload fields unless its specified.

        $payload['sub'] ??= $subject;
        $payload['iat'] ??= time();
        $payload['nbf'] ??= time();
        $payload['exp'] ??= time() + ($config->getTtl() ?? 0);

        if ($this->issuer) {
            $payload['iss'] ??= $this->issuer;
        }

        if ($config->getAudience()) {
            $payload['aud'] ??= $config->getAudience();
        }

        $jwsConfig = $config->getJwsConfig();
        $jweConfig = $config->getJweConfig();

        if ($jweConfig === null) {
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
}
