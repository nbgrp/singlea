<?php declare(strict_types=1);
// SPDX-License-Identifier: BSD-3-Clause

namespace SingleA\Bundles\Singlea\Service\Realm;

use Symfony\Bundle\SecurityBundle\Security\FirewallMap;
use Symfony\Component\HttpFoundation\Request;

final class RealmResolver implements RealmResolverInterface
{
    public function __construct(
        private readonly FirewallMap $firewallMap,
    ) {}

    public function resolve(Request $request): string
    {
        $firewallConfig = $this->firewallMap->getFirewallConfig($request);
        if (!$firewallConfig) {
            throw new \RuntimeException('Firewall config unreachable.');
        }

        return $firewallConfig->getName();
    }
}
