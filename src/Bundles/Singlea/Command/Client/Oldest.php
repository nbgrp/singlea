<?php declare(strict_types=1);
// SPDX-License-Identifier: BSD-3-Clause

namespace SingleA\Bundles\Singlea\Command\Client;

use SingleA\Contracts\Persistence\ClientManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Uid\UuidV6;

#[AsCommand(
    name: 'client:oldest',
    description: 'Find oldest client.',
)]
final class Oldest extends Command
{
    public function __construct(
        private ClientManagerInterface $clientManager,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $clientId = $this->clientManager->findOldest();
        if (!$clientId) {
            $io->info('There is no any client.');

            return self::SUCCESS;
        }

        $clientUuid = UuidV6::fromString($clientId);
        $timezone = new \DateTimeZone(date_default_timezone_get());
        $createdAt = $clientUuid->getDateTime()->setTimezone($timezone);
        $lastAccessedAt = $this->clientManager->getLastAccess($clientId)->setTimezone($timezone);

        $table = new Table($output);
        $table->setHeaders(['Client ID', 'Client UUID', 'Created at', 'Last access']);
        $table->addRow([
            $clientUuid->toBase58(),
            $clientId,
            $createdAt->format('d.m.Y H:i:s'),
            $lastAccessedAt->format('d.m.Y H:i:s'),
        ]);

        $table->render();

        return self::SUCCESS;
    }
}
