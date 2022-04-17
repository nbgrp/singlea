<?php declare(strict_types=1);
// SPDX-License-Identifier: BSD-3-Clause

namespace SingleA\Bundles\Singlea\Command\Client;

use SingleA\Contracts\Persistence\ClientManagerInterface;
use SingleA\Contracts\Persistence\FeatureConfigManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Uid\UuidV6;

#[AsCommand(
    name: 'client:remove',
    description: 'Remove client.',
)]
final class Remove extends Command
{
    /**
     * @param iterable<FeatureConfigManagerInterface> $configManagers
     */
    public function __construct(
        private readonly iterable $configManagers,
        private readonly ClientManagerInterface $clientManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('client-id', InputArgument::REQUIRED, 'Client ID.')
            ->addOption('yes', 'y', InputOption::VALUE_NONE, 'Automatic yes to remove confirm.')
        ;
    }

    protected function interact(InputInterface $input, OutputInterface $output): void
    {
        if ($input->getArgument('client-id')) {
            return;
        }

        $input->setArgument(
            'client-id',
            $this->getHelper('question')->ask($input, $output, new Question("Enter client ID:\n")),
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $clientUuid = self::getClientUuid($input);
        } catch (\InvalidArgumentException) {
            $io->error('Invalid client ID.');

            return self::FAILURE;
        }

        $clientId = (string) $clientUuid;
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

        if (!$this->getConfirm($input, $output)) {
            $io->info('Nothing were removed.');

            return self::SUCCESS;
        }

        $removed = $this->clientManager->remove($clientId);
        if ($removed) {
            $this->remove($clientId);
        }

        $io->info($removed ? 'Client was removed.' : 'Nothing were removed.');

        return self::SUCCESS;
    }

    private static function getClientUuid(InputInterface $input): UuidV6
    {
        return UuidV6::fromString($input->getArgument('client-id')); // @phpstan-ignore-line
    }

    /**
     * @psalm-suppress MixedInferredReturnType, MixedReturnStatement
     */
    private function getConfirm(InputInterface $input, OutputInterface $output): bool
    {
        if ($input->getOption('yes')) {
            return true;
        }

        $confirmation = new ConfirmationQuestion('Remove client? (y/N) ', false);

        return $this->getHelper('question')->ask($input, $output, $confirmation);
    }

    private function remove(string $clientId): void
    {
        foreach ($this->configManagers as $configManager) {
            $configManager->remove($clientId);
        }
    }
}
