<?php declare(strict_types=1);
// SPDX-License-Identifier: BSD-3-Clause

namespace SingleA\Bundles\Singlea\Event;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Allows changing response for login action and add additional processing for it.
 *
 * @see \SingleA\Bundles\Singlea\Service\Authentication\AuthenticationService
 */
final class LoginEvent extends Event
{
    public function __construct(
        private Request $request,
        private Response $response,
        private string $ticket,
    ) {}

    public function getRequest(): Request
    {
        return $this->request;
    }

    public function setResponse(Response $response): self
    {
        $this->response = $response;

        return $this;
    }

    public function getResponse(): Response
    {
        return $this->response;
    }

    public function getTicket(): string
    {
        return $this->ticket;
    }
}
