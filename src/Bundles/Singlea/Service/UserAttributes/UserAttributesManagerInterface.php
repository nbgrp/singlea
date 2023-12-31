<?php
// SPDX-License-Identifier: BSD-3-Clause

declare(strict_types=1);

namespace SingleA\Bundles\Singlea\Service\UserAttributes;

interface UserAttributesManagerInterface
{
    /**
     * Check for user attributes existence for the specified realm and ticket.
     */
    public function exists(string $realm, string $ticket): bool;

    /**
     * Store user attributes for the specific realm and return the access ticket.
     *
     * If the ticket already exists, it should be passed in an argument to prevent new ticket
     * creation.
     */
    public function persist(string $realm, string $userIdentifier, array $attributes, ?string $ticket = null): string;

    /**
     * Increase the lifetime of user attributes for the specified realm and ticket.
     */
    public function prolong(string $realm, string $ticket): bool;

    /**
     * Get user attributes item for the specified realm and ticket if exists.
     */
    public function find(string $realm, string $ticket): ?UserAttributesItemInterface;

    /**
     * Remove user attributes for the specified realm and ticket.
     */
    public function remove(string $realm, string $ticket): bool;

    /**
     * Remove user attributes by the specified identifier.
     */
    public function removeByUser(string $userIdentifier): bool;
}
