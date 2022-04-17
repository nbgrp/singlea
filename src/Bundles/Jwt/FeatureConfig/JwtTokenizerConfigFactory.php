<?php declare(strict_types=1);
// SPDX-License-Identifier: BSD-3-Clause

namespace SingleA\Bundles\Jwt\FeatureConfig;

use Jose\Component\Core\AlgorithmManagerFactory;
use Jose\Component\Core\JWK;
use Jose\Component\Encryption\Compression\CompressionMethodManagerFactory;
use Jose\Component\KeyManagement\JWKFactory;
use SingleA\Contracts\Tokenization\TokenizerConfigFactoryInterface;

/**
 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
 * @SuppressWarnings(PHPMD.NPathComplexity)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
final class JwtTokenizerConfigFactory implements TokenizerConfigFactoryInterface
{
    private array $availableAlgorithms;
    private array $availableCompressionMethods;

    public function __construct(
        private readonly ?int $configDefaultTtl,
        AlgorithmManagerFactory $algorithmManagerFactory,
        CompressionMethodManagerFactory $compressionMethodManagerFactory,
    ) {
        $this->availableAlgorithms = $algorithmManagerFactory->aliases();
        $this->availableCompressionMethods = $compressionMethodManagerFactory->aliases();
    }

    public function getConfigClass(): string
    {
        return JwtTokenizerConfig::class;
    }

    public function getKey(): string
    {
        return self::KEY;
    }

    public function getHash(): string
    {
        return 'jwt';
    }

    /**
     * @psalm-suppress MixedArgument, MixedArrayAccess
     */
    public function create(array $input, mixed &$output = null): JwtTokenizerConfig
    {
        $errors = array_filter([
            $this->validateTtl($input),
            $this->validateClaims($input),
            $this->validateJws($input),
            $this->validateJwe($input),
            $this->validateAudience($input),
        ]);

        if ($errors) {
            throw new \DomainException(implode("\n", $errors));
        }

        /** @psalm-suppress MixedAssignment */
        $output ??= [];

        return new JwtTokenizerConfig(
            \array_key_exists('ttl', $input)
                ? (int) $input['ttl']
                : $this->configDefaultTtl,
            $input['claims'] ?? null,
            new JwsConfig(
                $input['jws']['alg'],
                self::generateSignatureJwk(
                    $input['jws']['alg'],
                    $input['jws']['bits'] ?? null,
                    $output['jwk'], // @phpstan-ignore-line
                ),
            ),
            \array_key_exists('jwe', $input)
                ? new JweConfig(
                    $input['jwe']['alg'],
                    $input['jwe']['enc'],
                    $input['jwe']['zip'] ?? null,
                    new JWK($input['jwe']['jwk']),
                )
                : null,
            $input['aud'] ?? null,
        );
    }

    private static function generateSignatureJwk(string $algorithm, ?int $bits, mixed &$public): JWK
    {
        $values = [
            'alg' => $algorithm,
            'use' => 'sig',
        ];

        $jwk = match ($algorithm) {
            'ES256' => JWKFactory::createECKey('P-256', $values),
            'ES384' => JWKFactory::createECKey('P-384', $values),
            'ES512' => JWKFactory::createECKey('P-521', $values),
            'ES256K' => JWKFactory::createECKey('secp256k1', $values),
            'EdDSA' => JWKFactory::createOKPKey('Ed25519', $values),
            'RS256', 'PS256' => JWKFactory::createRSAKey($bits ?? 2048, $values),
            'RS384', 'PS384' => JWKFactory::createRSAKey($bits ?? 3072, $values),
            'RS512', 'PS512' => JWKFactory::createRSAKey($bits ?? 4096, $values),
            'HS256', 'HS384' => JWKFactory::createOctKey($bits ?? 1024, $values),
            'HS512' => JWKFactory::createOctKey($bits ?? 2048, $values),
            default => throw new \UnexpectedValueException('Cannot generate JWK for unsupported signature algorithm "'.$algorithm.'".'),
        };

        $public = $jwk->toPublic();

        return $jwk;
    }

    private function makeInvalidMessage(string $subKey, string $message): string
    {
        return 'The "'.$this->getKey().'.'.$subKey.'" parameter '.$message.'.';
    }

    private function validateTtl(array $input): ?string
    {
        if (!\array_key_exists('ttl', $input)) {
            return null;
        }

        if (!is_numeric($input['ttl']) || ((int) $input['ttl']) < 0) {
            return $this->makeInvalidMessage('ttl', 'must be a positive number or zero');
        }

        return null;
    }

    private function validateClaims(array $input): ?string
    {
        if (!\array_key_exists('claims', $input)) {
            return null;
        }

        if (!\is_array($input['claims'])) {
            return $this->makeInvalidMessage('claims', 'must be an array');
        }

        foreach ($input['claims'] as $claim) {
            if (!\is_string($claim)) {
                return $this->makeInvalidMessage('claims', 'must only contain a list of strings');
            }
        }

        return null;
    }

    private function validateJws(array $input): ?string
    {
        if (!\array_key_exists('jws', $input)) {
            return $this->makeInvalidMessage('jws', 'is required');
        }

        if (!\is_array($input['jws'])) {
            return $this->makeInvalidMessage('jws', 'must be an array');
        }

        if (!\array_key_exists('alg', $input['jws'])) {
            return $this->makeInvalidMessage('jws.alg', 'is required');
        }

        $error = $this->validateAlgorithm($input['jws']['alg']);
        if ($error) {
            return $this->makeInvalidMessage('jws.alg', $error);
        }

        /** @psalm-suppress MixedArrayAccess */
        if ($input['jws']['alg'][0] !== 'E' && \array_key_exists('bits', $input['jws'])) {
            /** @psalm-suppress UnhandledMatchCondition */
            $minBits = match ($input['jws']['alg']) { // @phpstan-ignore-line
                'HS256' => 256,
                'HS384' => 384,
                'RS256', 'RS384', 'RS512', 'PS256', 'PS384', 'PS512', 'HS512' => 512,
            };

            if (!is_numeric($input['jws']['bits']) || ((int) $input['jws']['bits']) < $minBits) {
                return $this->makeInvalidMessage('jws.bits', sprintf('must be a number greater than or equal to %d', $minBits));
            }
        }

        return null;
    }

    private function validateJwe(array $input): ?string
    {
        if (!\array_key_exists('jwe', $input)) {
            return null;
        }

        if (!\is_array($input['jwe'])) {
            return $this->makeInvalidMessage('jwe', 'must be an array');
        }

        $errors = [];

        foreach (['alg', 'enc', 'jwk'] as $key) {
            if (!\array_key_exists($key, $input['jwe'])) {
                $errors[] = $this->makeInvalidMessage('jwe.'.$key, 'is required');
            }
        }
        if ($errors) {
            return implode("\n", $errors);
        }

        foreach (['alg', 'enc'] as $key) {
            $error = $this->validateAlgorithm($input['jwe'][$key]);
            if ($error) {
                $errors[] = $this->makeInvalidMessage('jwe.'.$key, $error);
            }
        }
        if ($errors) {
            return implode("\n", $errors);
        }

        if (\array_key_exists('zip', $input['jwe']) && !\in_array($input['jwe']['zip'], $this->availableCompressionMethods, true)) {
            return $this->makeInvalidMessage('jwe.zip', 'contains unsupported compression method');
        }

        if (!\is_array($input['jwe']['jwk'])) {
            return $this->makeInvalidMessage('jwe.jwk', 'must be an array');
        }

        try {
            new JWK($input['jwe']['jwk']); // @phan-suppress-current-line PhanNoopNew
        } catch (\InvalidArgumentException $exception) {
            return $this->makeInvalidMessage('jwe.jwk', 'has invalid value: '.$exception->getMessage());
        }

        return null;
    }

    private function validateAlgorithm(mixed $algorithm): ?string
    {
        if (!\is_string($algorithm)) {
            return 'must be a string';
        }

        if (!\in_array($algorithm, $this->availableAlgorithms, true)) {
            return 'contains unsupported algorithm';
        }

        return null;
    }

    private function validateAudience(array $input): ?string
    {
        if (!\array_key_exists('aud', $input)) {
            return null;
        }

        if (!\is_string($input['aud'])) {
            return $this->makeInvalidMessage('aud', 'must be a string');
        }

        return null;
    }
}
