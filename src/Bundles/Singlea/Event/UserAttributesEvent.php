<?php declare(strict_types=1);
// SPDX-License-Identifier: BSD-3-Clause

namespace SingleA\Bundles\Singlea\Event;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Used for passing user attributes into SuccessfulLoginListener.
 *
 * @see \SingleA\Bundles\Singlea\EventListener\Security\SuccessfulLoginListener
 */
final class UserAttributesEvent extends Event
{
    private array $userAttributes = [];

    public function __construct(
        private Passport $passport,
        private TokenInterface $authenticatedToken,
        private Request $request,
    ) {}

    public function getPassport(): Passport
    {
        return $this->passport;
    }

    public function getAuthenticatedToken(): TokenInterface
    {
        return $this->authenticatedToken;
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    public function getUserAttributes(): array
    {
        return $this->userAttributes;
    }

    public function setUserAttributes(array $attributes): void
    {
        $this->userAttributes = $attributes;
    }
}
