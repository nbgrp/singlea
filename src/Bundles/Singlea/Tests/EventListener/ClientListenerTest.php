<?php declare(strict_types=1);
// SPDX-License-Identifier: BSD-3-Clause

namespace SingleA\Bundles\Singlea\Tests\EventListener;

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use SingleA\Bundles\Singlea\EventListener\ClientListener;
use SingleA\Contracts\Persistence\ClientManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * @covers \SingleA\Bundles\Singlea\EventListener\ClientListener
 *
 * @internal
 */
final class ClientListenerTest extends TestCase
{
    public function testNoSecret(): void
    {
        $listener = new ClientListener('client_id', 'secret', $this->createStub(ClientManagerInterface::class));
        $event = new RequestEvent(
            $this->createStub(KernelInterface::class),
            Request::create(''),
            HttpKernelInterface::MAIN_REQUEST,
        );

        $listener->decodeSecret($event);

        self::assertNull($event->getRequest()->attributes->get('__secret'));
    }

    public function testDecodeValidSecret(): void
    {
        $listener = new ClientListener('client_id', 'secret', $this->createStub(ClientManagerInterface::class));
        $event = new RequestEvent(
            $this->createStub(KernelInterface::class),
            Request::create('', parameters: ['secret' => 'dmFsaWQtc2VjcmV0']),
            HttpKernelInterface::MAIN_REQUEST,
        );

        $listener->decodeSecret($event);

        self::assertSame('valid-secret', $event->getRequest()->attributes->get('__secret'));
    }

    public function testDecodeInvalidSecret(): void
    {
        $secret = 'invalid-secret';
        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects(self::once())
            ->method('notice')
            ->with(self::anything(), [$secret])
        ;

        $listener = new ClientListener('client_id', 'secret', $this->createStub(ClientManagerInterface::class), $logger);
        $event = new RequestEvent(
            $this->createStub(KernelInterface::class),
            Request::create('', parameters: ['secret' => $secret]),
            HttpKernelInterface::MAIN_REQUEST,
        );

        $this->expectException(UnauthorizedHttpException::class);
        $this->expectExceptionMessage('Invalid client secret.');

        $listener->decodeSecret($event);
    }

    public function testNoClientId(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects(self::never())
            ->method('info')
        ;

        $listener = new ClientListener('client_id', 'secret', $this->createStub(ClientManagerInterface::class), $logger);
        $event = new RequestEvent(
            $this->createStub(KernelInterface::class),
            Request::create(''),
            HttpKernelInterface::MAIN_REQUEST,
        );

        $listener->decodeClientId($event);

        self::assertNull($event->getRequest()->attributes->get('__client_id'));
    }

    public function testDecodeValidClientId(): void
    {
        $clientManager = $this->createMock(ClientManagerInterface::class);
        $clientManager
            ->expects(self::once())
            ->method('exists')
            ->with('1ec85fb6-4d5f-6204-a112-c71a46228559')
            ->willReturn(true)
        ;

        $listener = new ClientListener('client_id', 'secret', $clientManager);
        $event = new RequestEvent(
            $this->createStub(KernelInterface::class),
            Request::create('', parameters: ['client_id' => '4oUAttaBcDhMv36V2xDZsN']),
            HttpKernelInterface::MAIN_REQUEST,
        );

        $listener->decodeClientId($event);

        self::assertSame('1ec85fb6-4d5f-6204-a112-c71a46228559', $event->getRequest()->attributes->get('__client_id'));
    }

    public function testDecodeInvalidClientId(): void
    {
        $clientManager = $this->createMock(ClientManagerInterface::class);
        $clientManager
            ->expects(self::once())
            ->method('exists')
            ->with('')
            ->willReturn(false)
        ;

        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects(self::once())
            ->method('info')
        ;

        $listener = new ClientListener('client_id', 'secret', $clientManager, $logger);
        $event = new RequestEvent(
            $this->createStub(KernelInterface::class),
            Request::create('', parameters: ['client_id' => 'invalid-client-id']),
            HttpKernelInterface::MAIN_REQUEST,
        );

        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('Unknown client.');

        $listener->decodeClientId($event);
    }
}
