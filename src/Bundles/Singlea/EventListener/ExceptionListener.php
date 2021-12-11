<?php declare(strict_types=1);
// SPDX-License-Identifier: BSD-3-Clause

namespace SingleA\Bundles\Singlea\EventListener;

use Symfony\Component\ErrorHandler\Exception\FlattenException;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class ExceptionListener
{
    public function __construct(
        private bool $debug,
    ) {}

    #[AsEventListener(KernelEvents::EXCEPTION, priority: -10)]
    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = FlattenException::createFromThrowable($event->getThrowable());

        $data = [
            'type' => 'https://tools.ietf.org/html/rfc2616#section-10',
            'title' => 'An error occurred',
            'status' => $exception->getStatusCode(),
            'detail' => $this->debug ? $exception->getMessage() : $exception->getStatusText(),
        ];
        if ($this->debug) {
            $data['class'] = $exception->getClass();
            $data['trace'] = $exception->getTrace();
        }

        $event->setResponse(new JsonResponse(
            $data,
            $exception->getStatusCode(),
        ));
    }
}
