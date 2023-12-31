<?php
// SPDX-License-Identifier: BSD-3-Clause

declare(strict_types=1);

namespace SingleA\Bundles\Singlea\Tests\EventListener;

use PHPUnit\Framework\TestCase;
use SingleA\Bundles\Singlea\EventListener\ExceptionListener;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * @covers \SingleA\Bundles\Singlea\EventListener\ExceptionListener
 *
 * @internal
 */
final class ExceptionListenerTest extends TestCase
{
    /**
     * @dataProvider provideInvalidateSessionCases
     */
    public function testInvalidateSession(ExceptionEvent $event, bool $expectedSet): void
    {
        $session = $this->createMock(Session::class);
        $session
            ->expects($expectedSet ? self::once() : self::never())
            ->method('set')
        ;
        $request = Request::create('');
        $request->setSession($session);

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $listener = new ExceptionListener(false, $requestStack);
        $listener->invalidateSession($event);
    }

    public function provideInvalidateSessionCases(): iterable
    {
        yield 'Unsupported exception' => [
            'event' => new ExceptionEvent(
                $this->createStub(KernelInterface::class),
                Request::create(''),
                HttpKernelInterface::MAIN_REQUEST,
                new BadRequestHttpException('Some internal error info.'),
            ),
            'expectedSet' => false,
        ];

        yield 'Successful invalidation' => [
            'event' => new ExceptionEvent(
                $this->createStub(KernelInterface::class),
                Request::create(''),
                HttpKernelInterface::MAIN_REQUEST,
                new AccessDeniedException('AccessDenied.'),
            ),
            'expectedSet' => true,
        ];
    }

    /**
     * @dataProvider provideConvertExceptionToJsonResponseCases
     */
    public function testConvertExceptionToJsonResponse(bool $debug, ExceptionEvent $event, string $expected): void
    {
        $listener = new ExceptionListener($debug, $this->createMock(RequestStack::class));
        $listener->convertExceptionToJsonResponse($event);

        self::assertStringStartsWith($expected, $event->getResponse()->getContent());
    }

    public function provideConvertExceptionToJsonResponseCases(): iterable
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
