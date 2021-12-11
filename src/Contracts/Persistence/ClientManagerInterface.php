<?php declare(strict_types=1);
// SPDX-License-Identifier: BSD-3-Clause

namespace SingleA\Contracts\Persistence;

interface ClientManagerInterface
{
    /**
     * Does the client with the specified id exist.
     *
     * @param bool $touch Update the client last access time, if exists
     */
    public function exists(string $id, bool $touch = true): bool;

    /**
     * Update the client last access time.
     */
    public function touch(string $id): void;

    /**
     * Get the client with the specified id last access time.
     */
    public function getLastAccess(string $id): \DateTimeImmutable;

    /**
     * Get ids of the clients inactive since the specified time.
     *
     * @return iterable<string>
     */
    public function findInactiveSince(\DateTimeInterface $datetime): iterable;

    /**
     * Remove clients by the specified ids and return the number of actually removed clients.
     */
    public function remove(string ...$ids): int;
}
