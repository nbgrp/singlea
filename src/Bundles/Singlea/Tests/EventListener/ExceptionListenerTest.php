<?php declare(strict_types=1);
// SPDX-License-Identifier: BSD-3-Clause

namespace SingleA\Bundles\Singlea\Tests\EventListener;

use PHPUnit\Framework\TestCase;
use SingleA\Bundles\Singlea\EventListener\ExceptionListener;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * @covers \SingleA\Bundles\Singlea\EventListener\ExceptionListener
 *
 * @internal
 */
final class ExceptionListenerTest extends TestCase
{
    /**
     * @dataProvider onKernelExceptionProvider
     */
    public function testOnKernelException(bool $debug, ExceptionEvent $event, string $expected): void
    {
        $listener = new ExceptionListener($debug);
        $listener->onKernelException($event);

        self::assertStringStartsWith($expected, $event->getResponse()->getContent());
    }

    public function onKernelExceptionProvider(): \Generator
    {
        $event = new ExceptionEvent(
            $this->createStub(KernelInterface::class),
            Request::create(''),
            HttpKernelInterface::MAIN_REQUEST,
            new BadRequestHttpException('Some internal error info.'),
        );

        yield 'No debug' => [
            'debug' => false,
            'event' => $event,
            'expected' => '{"type":"https:\/\/tools.ietf.org\/html\/rfc2616#section-10","title":"An error occurred","status":400,"detail":"Bad Request"}',
        ];

        yield 'With debug' => [
            'debug' => true,
            'event' => $event,
            'expected' => '{"type":"https:\/\/tools.ietf.org\/html\/rfc2616#section-10","title":"An error occurred","status":400,"detail":"Some internal error info.","class":"Symfony\\\\Component\\\\HttpKernel\\\\Exception\\\\BadRequestHttpException","trace":[',
        ];
    }
}
