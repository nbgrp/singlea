<?php
// SPDX-License-Identifier: BSD-3-Clause

declare(strict_types=1);

namespace SingleA\Bundles\Singlea\Tests\Event;

use PHPUnit\Framework\TestCase;
use SingleA\Bundles\Singlea\Event\PayloadComposeEvent;

/**
 * @covers \SingleA\Bundles\Singlea\Event\PayloadComposeEvent
 *
 * @internal
 */
final class PayloadComposeEventTest extends TestCase
{
    public function testSetPayload(): void
    {
        $event = new PayloadComposeEvent(['foo' => 'bar']);
        self::assertSame(['foo' => 'bar'], $event->getPayload());

        $event->setPayload(['bar' => 'foo']);
        self::assertSame(['bar' => 'foo'], $event->getPayload());
    }
}
