<?php
// SPDX-License-Identifier: BSD-3-Clause

declare(strict_types=1);

namespace SingleA\Bundles\Singlea\Tests\Event;

use PHPUnit\Framework\TestCase;
use SingleA\Bundles\Singlea\Event\LoginEvent;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @covers \SingleA\Bundles\Singlea\Event\LoginEvent
 *
 * @internal
 */
final class LoginEventTest extends TestCase
{
    public function testSetResponse(): void
    {
        $event = new LoginEvent(Request::create(''), new Response('1'), 'ticket-value');
        self::assertSame('1', $event->getResponse()->getContent());

        $event->setResponse(new Response('2'));
        self::assertSame('2', $event->getResponse()->getContent());
    }
}
