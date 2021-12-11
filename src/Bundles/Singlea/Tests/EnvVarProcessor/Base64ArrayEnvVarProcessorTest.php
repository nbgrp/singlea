<?php declare(strict_types=1);
// SPDX-License-Identifier: BSD-3-Clause

namespace SingleA\Bundles\Singlea\Tests\EnvVarProcessor;

use PHPUnit\Framework\TestCase;
use SingleA\Bundles\Singlea\EnvVarProcessor\Base64ArrayEnvVarProcessor;

/**
 * @covers \SingleA\Bundles\Singlea\EnvVarProcessor\Base64ArrayEnvVarProcessor
 *
 * @internal
 */
final class Base64ArrayEnvVarProcessorTest extends TestCase
{
    public function testProvidedTypesKey(): void
    {
        self::assertSame('base64-array', key(Base64ArrayEnvVarProcessor::getProvidedTypes()));
    }

    public function testGetEnv(): void
    {
        $processor = new Base64ArrayEnvVarProcessor();

        self::assertSame(
            [
                hex2bin('aa3f4b610c59652cdb00'),
                hex2bin('31f75a286b2b7689d795'),
            ],
            $processor->getEnv('base64-array', 'csv:CSV_ENV', \Closure::fromCallable(static function (string $name): array {
                self::assertSame('csv:CSV_ENV', $name);

                return [
                    'qj9LYQxZZSzbAA==',
                    'MfdaKGsrdonXlQ==',
                ];
            })),
        );
    }
}
