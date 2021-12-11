<?php declare(strict_types=1);
// SPDX-License-Identifier: BSD-3-Clause

namespace SingleA\Bundles\Singlea\Tests\Controller\Feature;

use PHPUnit\Framework\TestCase;
use SingleA\Bundles\Singlea\Controller\Feature\Logout;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

/**
 * @covers \SingleA\Bundles\Singlea\Controller\Feature\Logout
 *
 * @internal
 */
final class LogoutTest extends TestCase
{
    public function testException(): void
    {
        $controller = new Logout();

        $this->expectException(ServiceUnavailableHttpException::class);
        $this->expectExceptionMessage('Invalid security settings.');

        $controller();
    }
}
