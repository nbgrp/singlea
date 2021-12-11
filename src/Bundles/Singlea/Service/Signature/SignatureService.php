<?php declare(strict_types=1);
// SPDX-License-Identifier: BSD-3-Clause

namespace SingleA\Bundles\Singlea\Service\Signature;

use SingleA\Bundles\Singlea\FeatureConfig\Signature\SignatureConfigInterface;
use SingleA\Bundles\Singlea\Session\KeepMetaCreatedSessionAuthenticationStrategy;
use SingleA\Bundles\Singlea\Utility\StringUtility;
use Symfony\Component\ErrorHandler\ErrorHandler;
use Symfony\Component\HttpFoundation\Request;

final class SignatureService implements SignatureServiceInterface
{
    private const VERIFY_CORRECT = 1;
    private const VERIFY_INCORRECT = 0;
    private const VERIFY_ERROR = -1;

    /**
     * @param array<string> $extraExcludeParameters
     */
    public function __construct(
        private int $requestTtl,
        private string $timestampQueryParameter,
        private string $signatureQueryParameter,
        private array $extraExcludeParameters = [],
    ) {}

    public function check(Request $request, SignatureConfigInterface $config): void
    {
        if (!$request->query->has($this->timestampQueryParameter)) {
            throw new \RuntimeException('Request does not contain timestamp.');
        }

        if (!$request->query->has($this->signatureQueryParameter)) {
            throw new \RuntimeException('Request does not contain signature.');
        }

        if ($this->isRequestExpired($request, $config)) {
            throw new \RuntimeException('Request expired.');
        }

        $encodedSignature = (string) $request->query->get($this->signatureQueryParameter);
        $signature = sodium_base642bin($encodedSignature, \SODIUM_BASE64_VARIANT_URLSAFE);

        /** @var array<scalar> $values */
        $values = array_diff_key(
            $request->query->all(),
            array_flip([$this->signatureQueryParameter, ...array_values($this->extraExcludeParameters)]),
        );
        ksort($values);

        if (self::isValidSignature(implode('.', $values), $signature, $config->getPublicKey(), $config->getMessageDigestAlgorithm())) {
            return;
        }

        throw new \RuntimeException('Signature is invalid.');
    }

    private static function isValidSignature(string $value, string $signature, string $publicKey, int $algorithm): bool
    {
        try {
            switch (ErrorHandler::call(static fn () => openssl_verify($value, $signature, $publicKey, $algorithm))) {
                case self::VERIFY_CORRECT:
                    return true;

                case self::VERIFY_ERROR:
                    throw new \ErrorException(openssl_error_string() ?: '(unknown error)');

                case self::VERIFY_INCORRECT:
                    return false;
            }
        } catch (\ErrorException $exception) {
            throw new \RuntimeException('Error occurred while signature verify: '.StringUtility::suffix($exception->getMessage(), '.'));
        }

        throw new \UnexpectedValueException('Unexpected openssl_verify return value.');
    }

    private function isRequestExpired(Request $request, SignatureConfigInterface $config): bool
    {
        $clientTime = (int) $request->query->get($this->timestampQueryParameter) + $config->getClientClockSkew();
        $serverTime = time();

        if ($request->hasSession()) {
            $session = $request->getSession();
            if ($session->has(KeepMetaCreatedSessionAuthenticationStrategy::INITIAL_META_CREATED) || $session->getMetadataBag()->getCreated() > 0) {
                $serverTime = (int) $session->get(KeepMetaCreatedSessionAuthenticationStrategy::INITIAL_META_CREATED, $session->getMetadataBag()->getCreated()); // @phpstan-ignore-line
            }
        }

        return $clientTime > $serverTime || $serverTime - $this->requestTtl > $clientTime;
    }
}
