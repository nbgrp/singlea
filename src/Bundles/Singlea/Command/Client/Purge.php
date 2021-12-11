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
    name: 'client:purge',
    description: 'Remove inactive outdated clients.',
)]
final class Purge extends Command
{
    /**
     * @param iterable<FeatureConfigManagerInterface> $configManagers
     */
    public function __construct(
        private iterable $configManagers,
        private ClientManagerInterface $clientManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('days', InputArgument::REQUIRED, 'Maximum allowed inactive period (days).')
            ->addOption('yes', 'y', InputOption::VALUE_NONE, 'Automatic yes to remove confirm.')
        ;
    }

    protected function interact(InputInterface $input, OutputInterface $output): void
    {
        /** @var string|null $days */
        $days = $input->getArgument('days');
        if ($days !== null) {
            return;
        }

        $input->setArgument(
            'days',
            $this->getHelper('question')->ask($input, $output, new Question('Maximum allowed inactive period (days): ')),
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $days = self::getDays($input);
        if (!$days) {
            $io->error('Maximum allowed inactive period must be specified as positive integer.');

            return self::FAILURE;
        }

        $timezone = new \DateTimeZone(date_default_timezone_get());
        $since = (new \DateTimeImmutable("-{$days} days"))->setTimezone($timezone);

        $table = new Table($output);
        $table->setHeaders(['Client ID', 'Client UUID', 'Created at', 'Last access']);

        $ids = [...$this->clientManager->findInactiveSince($since)];
        array_walk($ids, function (string $id) use ($timezone, $table): void {
            $uuid = UuidV6::fromString($id);
            $createdAt = $uuid->getDateTime()->setTimezone($timezone);
            $lastAccessedAt = $this->clientManager->getLastAccess($id)->setTimezone($timezone);

            $table->addRow([
                $uuid->toBase58(),
                $id,
                $createdAt->format('d.m.Y H:i:s'),
                $lastAccessedAt->format('d.m.Y H:i:s'),
            ]);
        });

        if (empty($ids)) {
            $io->info('There is no inactive clients since '.$since->format('d.m.Y H:i:s'));

            return self::SUCCESS;
        }

        $table->render();

        if (!$this->getConfirm($input, $output)) {
            $io->info('Nothing were removed.');

            return self::SUCCESS;
        }

        /** @psalm-suppress MixedArgument */
        $removed = $this->clientManager->remove(...$ids);
        if ($removed) {
            $this->remove($ids);
        }

        $io->info(sprintf('%d client%s removed.', $removed, $removed > 1 ? 's were' : ' was'));

        return self::SUCCESS;
    }

    private static function getDays(InputInterface $input): ?int
    {
        $days = $input->getArgument('days');
        if (is_numeric($days) && (int) $days > 0) {
            return (int) $days;
        }

        return null;
    }

    /**
     * @psalm-suppress MixedInferredReturnType, MixedReturnStatement
     */
    private function getConfirm(InputInterface $input, OutputInterface $output): bool
    {
        if ($input->getOption('yes')) {
            return true;
        }

        $confirmation = new ConfirmationQuestion('Remove clients? (y/N) ', false);

        return $this->getHelper('question')->ask($input, $output, $confirmation);
    }

    /**
     * @param array<string> $ids
     */
    private function remove(array $ids): void
    {
        foreach ($this->configManagers as $configManager) {
            $configManager->remove(...$ids);
        }
    }
}
