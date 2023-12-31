<?php
// SPDX-License-Identifier: BSD-3-Clause

declare(strict_types=1);

namespace SingleA\Bundles\Singlea\Service\Signature;

use Psr\Log\LoggerInterface;
use SingleA\Bundles\Singlea\FeatureConfig\Signature\SignatureConfigInterface;
use Symfony\Component\ErrorHandler\ErrorHandler;
use Symfony\Component\HttpFoundation\Request;

final class SignatureService implements SignatureServiceInterface
{
    public const REQUEST_RECEIVED_AT = '_rra';

    private const VERIFY_CORRECT = 1;
    private const VERIFY_INCORRECT = 0;
    private const VERIFY_ERROR = -1;

    /**
     * @param array<string> $extraExcludeParameters
     */
    public function __construct(
        private readonly int $requestTtl,
        private readonly string $timestampQueryParameter,
        private readonly string $signatureQueryParameter,
        private readonly array $extraExcludeParameters = [],
        private readonly ?LoggerInterface $logger = null,
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

        if ($this->isValidSignature(implode('.', $values), $signature, $config->getPublicKey(), $config->getMessageDigestAlgorithm())) {
            return;
        }

        throw new \RuntimeException('Signature is invalid.');
    }

    private function isRequestExpired(Request $request, SignatureConfigInterface $config): bool
    {
        $clientTime = (int) $request->query->get($this->timestampQueryParameter) + $config->getClientClockSkew();
        $serverTime = time();

        if ($request->hasSession()) {
            $session = $request->getSession();
            if ($session->has(self::REQUEST_RECEIVED_AT)) {
                $serverTime = (int) $session->get(self::REQUEST_RECEIVED_AT); // @phpstan-ignore-line
                $session->remove(self::REQUEST_RECEIVED_AT);
            }
        }

        $this->logger?->debug(sprintf('Request expiration check: server time - %d, client time - %d', $serverTime, $clientTime));

        return $clientTime > $serverTime || $serverTime - $this->requestTtl > $clientTime;
    }

    private function isValidSignature(string $value, string $signature, string $publicKey, int $algorithm): bool
    {
        $this->logger?->debug('Request signature: checked value "'.$value.'"');

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
            throw new \RuntimeException('Error occurred while signature verify: '.rtrim($exception->getMessage(), '.').'.');
        }

        throw new \UnexpectedValueException('Unexpected openssl_verify return value.');
    }
}
