<?php
// SPDX-License-Identifier: BSD-3-Clause

declare(strict_types=1);

namespace SingleA\Bundles\Singlea\EnvVarProcessor;

use Symfony\Component\DependencyInjection\EnvVarProcessorInterface;

final class Base64ArrayEnvVarProcessor implements EnvVarProcessorInterface
{
    public static function getProvidedTypes(): array
    {
        return ['base64-array' => 'array'];
    }

    public function getEnv(string $prefix, string $name, \Closure $getEnv): array
    {
        return array_map(
            static fn (string $value): string => sodium_base642bin(
                $value,
                \strlen($value) % 4 === 0
                    ? \SODIUM_BASE64_VARIANT_ORIGINAL
                    : \SODIUM_BASE64_VARIANT_ORIGINAL_NO_PADDING,
            ),
            (array) $getEnv($name),
        );
    }
}
