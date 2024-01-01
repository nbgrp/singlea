<?php
// SPDX-License-Identifier: BSD-3-Clause

declare(strict_types=1);

namespace SingleA\Bundles\Singlea\EventListener;

use SingleA\Bundles\Singlea\Service\Signature\SignatureService;
use Symfony\Component\ErrorHandler\Exception\FlattenException;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\Exception\SessionNotFoundException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

final readonly class ExceptionListener
{
    public function __construct(
        private bool $debug,
        private RequestStack $requestStack,
    ) {}

    #[AsEventListener(KernelEvents::EXCEPTION, priority: 10)]
    public function invalidateSession(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        if (!$exception instanceof AuthenticationException && !$exception instanceof AccessDeniedException) {
            return;
        }

        try {
            $session = $this->requestStack->getSession();
            $session->set(SignatureService::REQUEST_RECEIVED_AT, time());
        } catch (SessionNotFoundException) {
            // no-op
        }
    }

    #[AsEventListener(KernelEvents::EXCEPTION, priority: -10)]
    public function convertExceptionToJsonResponse(ExceptionEvent $event): void
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
