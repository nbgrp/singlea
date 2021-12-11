<?php declare(strict_types=1);
// SPDX-License-Identifier: BSD-3-Clause

namespace SingleA\Bundles\Singlea\Service\Tokenization;

use Psr\Log\LoggerInterface;
use SingleA\Bundles\Singlea\Event\PayloadComposeEvent;
use SingleA\Contracts\PayloadFetcher\FetcherConfigInterface;
use SingleA\Contracts\PayloadFetcher\FetcherInterface;
use SingleA\Contracts\Tokenization\TokenizerConfigInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class PayloadComposer implements PayloadComposerInterface
{
    /**
     * @param iterable<FetcherInterface> $payloadFetchers
     */
    public function __construct(
        private iterable $payloadFetchers,
        private EventDispatcherInterface $eventDispatcher,
        private ?LoggerInterface $logger = null,
    ) {}

    public function compose(
        array $userAttributes,
        TokenizerConfigInterface $tokenizerConfig,
        ?FetcherConfigInterface $fetcherConfig,
    ): array {
        $event = $this->eventDispatcher->dispatch(new PayloadComposeEvent(self::composeByClaims($userAttributes, $tokenizerConfig->getClaims() ?? [])));
        $payload = $event->getPayload();

        if ($fetcherConfig) {
            $payloadFetcher = $this->getFetcher($fetcherConfig);

            $start = hrtime(true);
            $payload = array_merge($payload, $payloadFetcher->fetch(
                self::composeByClaims($userAttributes, $fetcherConfig->getClaims() ?? []),
                $fetcherConfig,
            ));
            $this->logger?->debug(sprintf('The token payload was augmented by fetcher from %s in %.2f ms.', $fetcherConfig->getEndpoint(), ((float) (hrtime(true) - $start)) / 1e+6));
        }

        return $payload;
    }

    private static function composeByClaims(array $userAttributes, array $claims): array
    {
        $payload = [];
        foreach ($claims as $claim) {
            if (!\is_string($claim)) {
                continue;
            }

            $isArrayValue = str_ends_with($claim, '[]');
            $normalizedClaim = $isArrayValue ? substr($claim, 0, -2) : $claim;

            if (!\array_key_exists($normalizedClaim, $userAttributes)) {
                continue;
            }

            $payload[$claim] = (array) $userAttributes[$normalizedClaim];
            if (!$isArrayValue) {
                /** @psalm-suppress MixedAssignment */
                $payload[$claim] = reset($payload[$claim]);
            }
        }

        return $payload;
    }

    private function getFetcher(FetcherConfigInterface $config): FetcherInterface
    {
        foreach ($this->payloadFetchers as $fetcher) {
            if ($fetcher->supports($config)) {
                return $fetcher;
            }
        }

        throw new \RuntimeException('Payload fetcher for config of type '.$config::class.' not configured.');
    }
}
