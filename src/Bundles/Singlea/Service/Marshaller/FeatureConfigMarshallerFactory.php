<?php declare(strict_types=1);
// SPDX-License-Identifier: BSD-3-Clause

namespace SingleA\Bundles\Singlea\Service\Marshaller;

use SingleA\Bundles\Singlea\Utility\IgbinaryUtility;
use SingleA\Contracts\FeatureConfig\FeatureConfigInterface;
use SingleA\Contracts\Marshaller\FeatureConfigMarshallerInterface;
use Symfony\Component\ErrorHandler\ErrorHandler;

final class FeatureConfigMarshallerFactory
{
    private bool $useIgbinarySerialize;

    public function __construct(
        ?bool $useIgbinarySerialize = null,
    ) {
        $this->useIgbinarySerialize = IgbinaryUtility::checkAvailability($useIgbinarySerialize);
    }

    /**
     * @param class-string $interface Feature config interface FQCN
     */
    public function __invoke(string $interface): FeatureConfigMarshallerInterface
    {
        if (!interface_exists($interface)) {
            throw new \UnexpectedValueException('An interface '.$interface.' does not exists.');
        }

        if (!is_subclass_of($interface, FeatureConfigInterface::class)) {
            throw new \UnexpectedValueException('Feature config marshaller can be initialized by an interface extends '.FeatureConfigInterface::class.' only.');
        }

        /** @psalm-suppress InvalidArgument */
        return new class($interface, $this->useIgbinarySerialize, self::class.'::handleUnserializeCallback') implements FeatureConfigMarshallerInterface {
            /**
             * @param class-string<FeatureConfigInterface> $interface
             * @param callable-string                      $unserializeCallbackHandler
             */
            public function __construct(
                private readonly string $interface,
                private readonly bool $useIgbinarySerialize,
                private readonly string $unserializeCallbackHandler,
            ) {}

            public function supports(FeatureConfigInterface|string $config): bool
            {
                return is_a($config, $this->interface, true);
            }

            public function marshall(FeatureConfigInterface $config): string
            {
                if (!($config instanceof $this->interface)) {
                    throw new \UnexpectedValueException('Marshaller supports only '.$this->interface.' objects, '.$config::class.' passed.');
                }

                try {
                    return $this->useIgbinarySerialize
                        ? igbinary_serialize($config) ?? throw new \RuntimeException('Cannot serialize config.') // @phan-suppress-current-line PhanPossiblyFalseTypeReturn
                        : serialize($config);
                } catch (\Throwable $exception) {
                    throw new \ValueError($exception->getMessage(), 0, $exception);
                }
            }

            public function unmarshall(string $value): FeatureConfigInterface
            {
                $unserializeCallbackHandler = ini_set('unserialize_callback_func', $this->unserializeCallbackHandler);

                try {
                    $config = ErrorHandler::call(static fn (): mixed => ($value[1] ?? ':') === ':' ? unserialize($value) : igbinary_unserialize($value));
                    if (!($config instanceof $this->interface)) {
                        throw new \UnexpectedValueException('Unmarshalled value must be an instance of '.$this->interface.', '.get_debug_type($config).' found.');
                    }

                    return $config;
                } catch (\ErrorException $exception) {
                    throw new \DomainException('Failed to unserialize values: '.$exception->getMessage());
                } finally {
                    if ($unserializeCallbackHandler) {
                        ini_set('unserialize_callback_func', $unserializeCallbackHandler);
                    }
                }
            }
        };
    }

    /**
     * @internal
     */
    public static function handleUnserializeCallback(string $class): void
    {
        throw new \DomainException('Class '.$class.' not found. Maybe you forgot to require necessary feature package?');
    }
}
