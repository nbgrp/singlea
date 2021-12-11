<?php declare(strict_types=1);
// SPDX-License-Identifier: BSD-3-Clause

namespace SingleA\Bundles\Singlea\Tests\EventListener;

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use SingleA\Bundles\Singlea\EventListener\TicketListener;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * @covers \SingleA\Bundles\Singlea\EventListener\TicketListener
 *
 * @internal
 */
final class TicketListenerTest extends TestCase
{
    public function testNoTicket(): void
    {
        $listener = new TicketListener('X-Ticket');
        $event = new RequestEvent(
            $this->createStub(KernelInterface::class),
            Request::create(''),
            HttpKernelInterface::MAIN_REQUEST,
        );

        $listener->decodeTicket($event);

        self::assertNull($event->getRequest()->attributes->get('__ticket'));
    }

    public function testDecodeValidTicket(): void
    {
        $listener = new TicketListener('X-Ticket');
        $event = new RequestEvent(
            $this->createStub(KernelInterface::class),
            Request::create('', server: ['HTTP_X_TICKET' => 'dmFsaWQtdGlja2V0']),
            HttpKernelInterface::MAIN_REQUEST,
        );

        $listener->decodeTicket($event);

        self::assertSame('valid-ticket', $event->getRequest()->attributes->get('__ticket'));
    }

    public function testDecodeInvalidTicket(): void
    {
        $ticket = 'invalid-encoded-ticket';
        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects(self::once())
            ->method('notice')
            ->with(self::anything(), [$ticket])
        ;

        $listener = new TicketListener('X-Ticket', $logger);
        $event = new RequestEvent(
            $this->createStub(KernelInterface::class),
            Request::create('', server: ['HTTP_X_TICKET' => $ticket]),
            HttpKernelInterface::MAIN_REQUEST,
        );

        $this->expectException(UnauthorizedHttpException::class);
        $this->expectExceptionMessage('Invalid ticket.');

        $listener->decodeTicket($event);
    }
}
