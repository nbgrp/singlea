<?php
// SPDX-License-Identifier: BSD-3-Clause

declare(strict_types=1);

namespace SingleA\Bundles\Singlea\Service\Client;

use Psr\Log\LoggerInterface;
use SingleA\Contracts\FeatureConfig\FeatureConfigFactoryInterface;
use SingleA\Contracts\FeatureConfig\FeatureConfigInterface;
use SingleA\Contracts\Persistence\ClientManagerInterface;
use SingleA\Contracts\Persistence\FeatureConfigManagerInterface;
use Symfony\Component\Uid\UuidV6;

final class RegistrationService implements RegistrationServiceInterface
{
    private \SplObjectStorage $requiredConfigManagers;

    /**
     * @param iterable<FeatureConfigFactoryInterface> $configFactories
     * @param iterable<FeatureConfigManagerInterface> $configManagers
     */
    public function __construct(
        private readonly iterable $configFactories,
        private readonly iterable $configManagers,
        private readonly ClientManagerInterface $clientManager,
        private readonly ?LoggerInterface $logger = null,
    ) {
        $this->requiredConfigManagers = new \SplObjectStorage();
    }

    public function register(array $input): RegistrationResult
    {
        $clientId = new UuidV6();
        $normalizedId = (string) $clientId;
        $secret = random_bytes(\SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);

        $configs = [];
        $output = [];

        try {
            $this->initRequired();

            foreach ($input as $key => $data) {
                if (!\is_array($data)) {
                    throw new \InvalidArgumentException(sprintf('Registration data in key "%s" must be an array.', $key));
                }

                /** @psalm-suppress MixedAssignment */
                [$configs[$key], $output[$key]] = $this->processRegistrationData((string) $key, $data);
                $this->logger?->debug('Config for client "'.$normalizedId.'" created.', [$key]);
            }

            foreach ($configs as $config) {
                $this->persist($normalizedId, $config, $secret);
                $this->logger?->debug('Config '.$config::class.' for client "'.$normalizedId.'" persisted.');
            }

            $this->checkRequired();

            $this->clientManager->touch($normalizedId);

            return new RegistrationResult(
                $clientId,
                sodium_bin2base64($secret, \SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING),
                array_filter($output),
            );
        } catch (\Throwable $exception) {
            $this->logger?->error('An exception caught during the client registration.');

            $this->remove($normalizedId);
            $this->logger?->debug('Client "'.$normalizedId.'" removed with configs.');

            throw $exception;
        }
    }

    /**
     * @return array{0: FeatureConfigInterface, 1: mixed}
     */
    private function processRegistrationData(string $key, array $input): array
    {
        foreach ($this->configFactories as $configFactory) {
            if ($configFactory->getKey() === $key && $configFactory->getHash() === ($input['#'] ?? $key)) {
                $output = null;
                $config = $configFactory->create($input, $output);

                return [$config, $output];
            }
        }

        throw new \UnexpectedValueException('Unsupported feature registration key "'.$key.'" detected.');
    }

    private function persist(string $id, FeatureConfigInterface $config, string $secret): void
    {
        $persisted = false;

        foreach ($this->configManagers as $configManager) {
            if ($configManager->supports($config)) {
                $configManager->persist($id, $config, $secret);
                $persisted = true;
                $this->requiredConfigManagers->detach($configManager);
            }
        }

        if (!$persisted) {
            throw new \RuntimeException('Config '.$config::class.' cannot be persisted.');
        }
    }

    private function remove(string $id): void
    {
        foreach ($this->configManagers as $configManager) {
            $configManager->remove($id);
        }
        $this->clientManager->remove($id);
    }

    private function initRequired(): void
    {
        $this->requiredConfigManagers = new \SplObjectStorage();

        foreach ($this->configManagers as $configManager) {
            if ($configManager->isRequired()) {
                /** @psalm-suppress InvalidArgument */
                $this->requiredConfigManagers->attach($configManager);
            }
        }
    }

    private function checkRequired(): void
    {
        if ($this->requiredConfigManagers->count() > 0) {
            throw new \DomainException('Registration request does not contain all required config settings.');
        }
    }
}
