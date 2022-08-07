<?php declare(strict_types=1);
// SPDX-License-Identifier: BSD-3-Clause

namespace SingleA\Bundles\JwtFetcher\FeatureConfig;

use Jose\Component\Core\AlgorithmManagerFactory;
use Jose\Component\Core\JWK;
use Jose\Component\Encryption\Compression\CompressionMethodManagerFactory;
use Jose\Component\KeyManagement\JWKFactory;
use SingleA\Contracts\PayloadFetcher\FetcherConfigFactoryInterface;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 *
 * phpcs:disable SlevomatCodingStandard.Complexity.Cognitive
 */
final class JwtFetcherConfigFactory implements FetcherConfigFactoryInterface
{
    private array $availableAlgorithms;
    private array $availableCompressionMethods;

    public function __construct(
        private readonly bool $httpsOnly,
        AlgorithmManagerFactory $algorithmManagerFactory,
        CompressionMethodManagerFactory $compressionMethodManagerFactory,
    ) {
        $this->availableAlgorithms = $algorithmManagerFactory->aliases();
        $this->availableCompressionMethods = $compressionMethodManagerFactory->aliases();
    }

    public function getConfigClass(): string
    {
        return JwtFetcherConfig::class;
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
     * @psalm-suppress MixedAssignment, MixedArgument, MixedArrayAccess
     */
    public function create(array $input, mixed &$output = null): JwtFetcherConfig
    {
        $errors = array_filter([
            $this->validateEndpoint($input),
            $this->validateClaims($input),
            $this->validateRequestSettings($input),
            $this->validateResponseSettings($input),
        ]);

        if ($errors) {
            throw new \DomainException(implode("\n", $errors));
        }

        $output ??= [];

        return new JwtFetcherConfig(
            $input['endpoint'],
            $input['claims'] ?? null,
            new JwsConfig(
                $input['request']['jws']['alg'],
                self::generateJwk(
                    $input['request']['jws']['alg'],
                    $input['request']['jws']['bits'] ?? null,
                    $output['request']['jwk'], // @phpstan-ignore-line
                    'sig',
                ),
            ),
            \array_key_exists('jwe', $input['request'])
                ? new JweConfig(
                    $input['request']['jwe']['alg'],
                    $input['request']['jwe']['enc'],
                    $input['request']['jwe']['zip'] ?? null,
                    new JWK($input['request']['jwe']['jwk']),
                )
                : null,
            $input['request']['options'] ?? null,
            new JwsConfig(
                $input['response']['jws']['alg'],
                new JWK($input['response']['jws']['jwk']),
            ),
            \array_key_exists('jwe', $input['response'])
                ? new JweConfig(
                    $input['response']['jwe']['alg'],
                    $input['response']['jwe']['enc'],
                    $input['response']['jwe']['zip'] ?? null,
                    self::generateJwk(
                        $input['response']['jwe']['alg'],
                        $input['response']['jwe']['bits'] ?? null,
                        $output['response']['jwk'], // @phpstan-ignore-line
                        'enc',
                    ),
                )
                : null,
        );
    }

    private static function generateJwk(string $algorithm, ?int $bits, mixed &$public, string $use): JWK
    {
        $values = [
            'alg' => $algorithm,
            'use' => $use,
        ];

        $jwk = match ($algorithm) {
            'ES256' => JWKFactory::createECKey('P-256', $values),
            'ES384' => JWKFactory::createECKey('P-384', $values),
            'ES512' => JWKFactory::createECKey('P-521', $values),
            'ES256K' => JWKFactory::createECKey('secp256k1', $values),
            'EdDSA' => JWKFactory::createOKPKey('Ed25519', $values),
            'ECDH-ES', 'ECDH-ES+A128KW', 'ECDH-ES+A192KW', 'ECDH-ES+A256KW' => JWKFactory::createOKPKey('X25519', $values),
            'RS256', 'PS256', 'RSA1_5', 'RSA-OAEP', 'RSA-OAEP-256' => JWKFactory::createRSAKey($bits ?? 2048, $values),
            'RS384', 'PS384', 'RSA-OAEP-384' => JWKFactory::createRSAKey($bits ?? 3072, $values),
            'RS512', 'PS512', 'RSA-OAEP-512' => JWKFactory::createRSAKey($bits ?? 4096, $values),
            'chacha20-poly1305', 'A128KW', 'A192KW', 'A256KW', 'A128GCM', 'A192GCM', 'A256GCM', 'A128GCMKW', 'A192GCMKW', 'A256GCMKW', 'A128CTR', 'A192CTR', 'A256CTR', 'A128CCM-16-64', 'A128CCM_16_128', 'A128CCM_64_64', 'A128CCM_64_128', 'A256CCM-16-64', 'A256CCM_16_128', 'A256CCM_64_64', 'A256CCM_64_128' => JWKFactory::createOctKey($bits ?? 256, $values),
            'HS256', 'HS384', 'PBES2-HS256+A128KW', 'PBES2-HS384+A192KW', 'A128CBC-HS256', 'A192CBC-HS384' => JWKFactory::createOctKey($bits ?? 1024, $values),
            'HS512', 'PBES2-HS512+A256KW', 'A256CBC-HS512' => JWKFactory::createOctKey($bits ?? 2048, $values),
            default => throw new \UnexpectedValueException('Cannot generate JWK for unsupported signature algorithm "'.$algorithm.'".'),
        };

        $public = $jwk->toPublic();

        return $jwk;
    }

    private function makeInvalidMessage(string $subKey, string $message): string
    {
        return 'The "'.$this->getKey().'.'.$subKey.'" parameter '.$message.'.';
    }

    private function validateEndpoint(array $input): ?string
    {
        if (!\array_key_exists('endpoint', $input)) {
            return $this->makeInvalidMessage('endpoint', 'is required');
        }

        if (!\is_string($input['endpoint'])) {
            return $this->makeInvalidMessage('endpoint', 'must be a string');
        }

        $validScheme = $this->httpsOnly ? 'https' : 'http';
        if (!str_starts_with((string) parse_url($input['endpoint'], \PHP_URL_SCHEME), $validScheme)) {
            return $this->makeInvalidMessage('endpoint', 'must be an URL with '.$validScheme.' scheme');
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

        return null;
    }

    private function validateRequestSettings(array $input): ?string
    {
        if (!\array_key_exists('request', $input)) {
            return $this->makeInvalidMessage('request', 'is required');
        }

        if (!\is_array($input['request'])) {
            return $this->makeInvalidMessage('request', 'must be an array');
        }

        $error = $this->validateRequestJws($input['request']);
        if ($error) {
            return $error;
        }

        $error = $this->validateRequestJwe($input['request']);
        if ($error) {
            return $error;
        }

        $error = $this->validateRequestOptions($input['request']);
        if ($error) {
            return $error;
        }

        return null;
    }

    private function validateRequestJws(array $input): ?string
    {
        if (!\array_key_exists('jws', $input)) {
            return $this->makeInvalidMessage('request.jws', 'is required');
        }

        if (!\is_array($input['jws'])) {
            return $this->makeInvalidMessage('request.jws', 'must be an array');
        }

        if (!\array_key_exists('alg', $input['jws'])) {
            return $this->makeInvalidMessage('request.jws.alg', 'is required');
        }

        $error = $this->validateAlgorithm($input['jws']['alg']);
        if ($error) {
            return $this->makeInvalidMessage('request.jws.alg', $error);
        }

        /** @psalm-suppress MixedArrayAccess */
        if ($input['jws']['alg'][0] !== 'E' && \array_key_exists('bits', $input['jws'])) {
            /** @psalm-suppress UnhandledMatchCondition */
            $minBits = match ($input['jws']['alg']) { // @phpstan-ignore-line
                'A128KW', 'A128GCMKW', 'A128CTR', 'chacha20-poly1305' => 128,
                'A192KW', 'A192GCMKW', 'A192CTR' => 192,
                'HS256', 'A256KW', 'A256GCMKW', 'A256CTR', 'PBES2-HS256+A128KW' => 256,
                'HS384', 'PBES2-HS384+A192KW' => 384,
                'RS256', 'RS384', 'RS512', 'PS256', 'PS384', 'PS512', 'RSA-OAEP', 'RSA-OAEP-256', 'RSA-OAEP-384', 'RSA-OAEP-512', 'RSA1_5', 'HS512', 'PBES2-HS512+A256KW' => 512,
            };

            if (!is_numeric($input['jws']['bits']) || ((int) $input['jws']['bits']) < $minBits) {
                return $this->makeInvalidMessage('request.jws.bits', sprintf('must be a number greater than or equal to %d', $minBits));
            }
        }

        return null;
    }

    private function validateRequestJwe(array $input): ?string
    {
        if (!\array_key_exists('jwe', $input)) {
            return null;
        }

        if (!\is_array($input['jwe'])) {
            return $this->makeInvalidMessage('request.jwe', 'must be an array');
        }

        $errors = [];

        foreach (['alg', 'enc', 'jwk'] as $key) {
            if (!\array_key_exists($key, $input['jwe'])) {
                $errors[] = $this->makeInvalidMessage('request.jwe.'.$key, 'is required');
            }
        }
        if ($errors) {
            return implode("\n", $errors);
        }

        foreach (['alg', 'enc'] as $key) {
            $error = $this->validateAlgorithm($input['jwe'][$key]);
            if ($error) {
                $errors[] = $this->makeInvalidMessage('request.jwe.'.$key, $error);
            }
        }
        if ($errors) {
            return implode("\n", $errors);
        }

        if (\array_key_exists('zip', $input['jwe']) && !\in_array($input['jwe']['zip'], $this->availableCompressionMethods, true)) {
            return $this->makeInvalidMessage('request.jwe.zip', 'contains unsupported compression method');
        }

        if (!\is_array($input['jwe']['jwk'])) {
            return $this->makeInvalidMessage('request.jwe.jwk', 'must be an array');
        }

        try {
            new JWK($input['jwe']['jwk']); // @phan-suppress-current-line PhanNoopNew
        } catch (\InvalidArgumentException $exception) {
            return $this->makeInvalidMessage('request.jwe.jwk', 'has invalid value: '.$exception->getMessage());
        }

        return null;
    }

    private function validateRequestOptions(array $input): ?string
    {
        if (!\array_key_exists('options', $input)) {
            return null;
        }

        if (!\is_array($input['options'])) {
            return $this->makeInvalidMessage('request.options', 'must be an array');
        }

        return null;
    }

    private function validateResponseSettings(array $input): ?string
    {
        if (!\array_key_exists('response', $input)) {
            return $this->makeInvalidMessage('response', 'is required');
        }

        if (!\is_array($input['response'])) {
            return $this->makeInvalidMessage('response', 'must be an array');
        }

        $error = $this->validateResponseJws($input['response']);
        if ($error) {
            return $error;
        }

        $error = $this->validateResponseJwe($input['response']);
        if ($error) {
            return $error;
        }

        return null;
    }

    private function validateResponseJws(array $input): ?string
    {
        if (!\array_key_exists('jws', $input)) {
            return $this->makeInvalidMessage('response.jws', 'is required');
        }

        if (!\is_array($input['jws'])) {
            return $this->makeInvalidMessage('response.jws', 'must be an array');
        }

        if (!\array_key_exists('alg', $input['jws'])) {
            return $this->makeInvalidMessage('response.jws.alg', 'is required');
        }

        $error = $this->validateAlgorithm($input['jws']['alg']);
        if ($error) {
            return $this->makeInvalidMessage('response.jws.alg', $error);
        }

        if (!\array_key_exists('jwk', $input['jws'])) {
            return $this->makeInvalidMessage('response.jws.jwk', 'is required');
        }

        if (!\is_array($input['jws']['jwk'])) {
            return $this->makeInvalidMessage('response.jws.jwk', 'must be an array');
        }

        try {
            new JWK($input['jws']['jwk']); // @phan-suppress-current-line PhanNoopNew
        } catch (\InvalidArgumentException $exception) {
            return $this->makeInvalidMessage('response.jws.jwk', 'has invalid value: '.$exception->getMessage());
        }

        return null;
    }

    private function validateResponseJwe(array $input): ?string
    {
        if (!\array_key_exists('jwe', $input)) {
            return null;
        }

        $errors = [];

        if (!\is_array($input['jwe'])) {
            return $this->makeInvalidMessage('response.jwe', 'must be an array');
        }

        foreach (['alg', 'enc'] as $key) {
            if (!\array_key_exists($key, $input['jwe'])) {
                $errors[] = $this->makeInvalidMessage('response.jwe.'.$key, 'is required');
            }
        }
        if ($errors) {
            return implode("\n", $errors);
        }

        foreach (['alg', 'enc'] as $key) {
            $error = $this->validateAlgorithm($input['jwe'][$key]);
            if ($error) {
                $errors[] = $this->makeInvalidMessage('response.jwe.'.$key, $error);
            }
        }
        if ($errors) {
            return implode("\n", $errors);
        }

        if (\array_key_exists('zip', $input['jwe']) && !\in_array($input['jwe']['zip'], $this->availableCompressionMethods, true)) {
            return $this->makeInvalidMessage('response.jwe.zip', 'contains unsupported compression method');
        }

        /** @psalm-suppress MixedArrayAccess */
        if ($input['jwe']['alg'][0] !== 'E' && \array_key_exists('bits', $input['jwe'])) {
            /** @psalm-suppress UnhandledMatchCondition */
            $minBits = match ($input['jwe']['alg']) { // @phpstan-ignore-line
                'A128KW', 'A128GCMKW', 'A128CTR', 'chacha20-poly1305' => 128,
                'A192KW', 'A192GCMKW', 'A192CTR' => 192,
                'A256KW', 'A256GCMKW', 'A256CTR', 'PBES2-HS256+A128KW' => 256,
                'PBES2-HS384+A192KW' => 384,
                'RSA-OAEP', 'RSA-OAEP-256', 'RSA-OAEP-384', 'RSA-OAEP-512', 'RSA1_5', 'PBES2-HS512+A256KW' => 512,
            };

            if (!is_numeric($input['jwe']['bits']) || ((int) $input['jwe']['bits']) < $minBits) {
                return $this->makeInvalidMessage('response.jwe.bits', sprintf('must be a number greater than or equal to %d', $minBits));
            }
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
}
