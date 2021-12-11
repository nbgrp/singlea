<?php declare(strict_types=1);
// SPDX-License-Identifier: BSD-3-Clause

namespace SingleA\Bundles\Singlea\Service\UserAttributes;

use SingleA\Bundles\Singlea\Utility\IgbinaryUtility;
use Symfony\Component\ErrorHandler\ErrorHandler;

final class UserAttributesMarshaller implements UserAttributesMarshallerInterface
{
    /** @var non-empty-list<string> */
    private array $keys;
    private bool $useIgbinarySerialize;

    public function __construct(
        mixed $keys,
        ?bool $useIgbinarySerialize = null,
    ) {
        if (!\is_array($keys)) {
            throw new \InvalidArgumentException('User keys must be provided as an array.');
        }

        if (empty($keys)) {
            throw new \InvalidArgumentException('At least one user attributes key must be provided.');
        }

        /** @psalm-suppress MixedArgument */
        $this->keys = array_values(array_map('strval', $keys));

        $this->useIgbinarySerialize = IgbinaryUtility::checkAvailability($useIgbinarySerialize);
    }

    /**
     * @internal
     */
    public static function handleUnserializeCallback(string $class): void
    {
        throw new \DomainException('Class '.$class.' not found. Maybe you forgot to require necessary feature package?');
    }

    public function marshall(array $attributes, string $ticket): string
    {
        try {
            $serialized = $this->useIgbinarySerialize
                ? igbinary_serialize($attributes) ?? throw new \RuntimeException('Cannot serialize user attributes.')
                : serialize($attributes);
        } catch (\Throwable $exception) {
            throw new \ValueError($exception->getMessage(), 0, $exception);
        }

        return $this->encrypt($serialized, $ticket); // @phan-suppress-current-line PhanPossiblyFalseTypeArgument
    }

    public function unmarshall(string $value, string $ticket): array
    {
        $unserializeCallbackHandler = ini_set('unserialize_callback_func', self::class.'::handleUnserializeCallback');

        try {
            $value = $this->decrypt($value, $ticket);
            $attributes = ErrorHandler::call(static fn (): mixed => ($value[1] ?? ':') === ':' ? unserialize($value) : igbinary_unserialize($value));

            if (!\is_array($attributes)) {
                throw new \DomainException('Unmarshalled data type must be an array, get '.get_debug_type($attributes).'.');
            }

            return $attributes;
        } catch (\ErrorException $exception) {
            throw new \RuntimeException('Failed to unserialize values: '.$exception->getMessage());
        } finally {
            if ($unserializeCallbackHandler) {
                ini_set('unserialize_callback_func', $unserializeCallbackHandler);
            }
        }
    }

    private function encrypt(string $value, string $ticket): string
    {
        return sodium_crypto_secretbox($value, $ticket, $this->keys[0]);
    }

    private function decrypt(string $value, string $ticket): string
    {
        foreach ($this->keys as $key) {
            $decrypted = sodium_crypto_secretbox_open($value, $ticket, $key);
            if ($decrypted !== false) {
                return $decrypted;
            }
        }

        throw new \RuntimeException('Cannot decrypt user attributes.');
    }
}
