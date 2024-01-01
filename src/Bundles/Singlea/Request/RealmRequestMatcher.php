<?php
// SPDX-License-Identifier: BSD-3-Clause

declare(strict_types=1);

namespace SingleA\Bundles\Singlea\Request;

use SingleA\Bundles\Singlea\EventListener\RealmListener;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestMatcherInterface;

final readonly class RealmRequestMatcher implements RequestMatcherInterface
{
    public function __construct(
        private string $realm,
    ) {}

    /**
     * @psalm-pure
     */
    public static function for(string $firewall): string
    {
        return self::class.'.'.$firewall;
    }

    public function matches(Request $request): bool
    {
        return $request->attributes->get(RealmListener::REALM_ATTRIBUTE) === $this->realm;
    }
}
