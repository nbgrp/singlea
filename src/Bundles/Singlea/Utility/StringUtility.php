<?php declare(strict_types=1);
// SPDX-License-Identifier: BSD-3-Clause

namespace SingleA\Bundles\Singlea\Utility;

final class StringUtility
{
    public static function prefix(string $string, string $prefix): string
    {
        if (str_starts_with($string, $prefix)) {
            return $string;
        }

        return $prefix.$string;
    }

    public static function suffix(string $string, string $suffix): string
    {
        if (str_ends_with($string, $suffix)) {
            return $string;
        }

        return $string.$suffix;
    }
}
