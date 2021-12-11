<?php declare(strict_types=1);
// SPDX-License-Identifier: BSD-3-Clause

namespace SingleA\Bundles\Singlea\Utility;

final class IgbinaryUtility
{
    public static function checkAvailability(?bool $forced = null): bool
    {
        /** @phpstan-ignore-next-line */
        $supported = \extension_loaded('igbinary') && version_compare('3.2.2', phpversion('igbinary'), '<'); // @phan-suppress-current-line PhanPossiblyFalseTypeArgumentInternal

        if ($forced && !$supported) {
            throw new \RuntimeException(\extension_loaded('igbinary')
                ? 'Please upgrade the "igbinary" PHP extension to v3.2.2 or higher.'
                : 'The "igbinary" PHP extension is not loaded.',
            );
        }

        return $forced ?? $supported;
    }
}
