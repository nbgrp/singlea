<?php declare(strict_types=1);
// SPDX-License-Identifier: BSD-3-Clause

namespace SingleA\Bundles\Singlea\Command\User;

use SingleA\Bundles\Singlea\Command\QuestionHelperTrait;
use SingleA\Bundles\Singlea\Service\UserAttributes\UserAttributesManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'user:logout',
    description: 'Forcibly logout user by identifier.',
)]
final class Logout extends Command
{
    use QuestionHelperTrait;

    public function __construct(
        private readonly UserAttributesManagerInterface $userAttributesManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('identifier', InputArgument::REQUIRED, 'The identifier of the user to be logged out.');
    }

    protected function interact(InputInterface $input, OutputInterface $output): void
    {
        /** @var string|null $identifier */
        $identifier = $input->getArgument('identifier');
        if ($identifier !== null) {
            return;
        }

        $input->setArgument(
            'identifier',
            $this->getQuestionHelper()->ask($input, $output, new Question('The identifier of the user to be logged out: ')),
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $userIdentifier = $input->getArgument('identifier');

        if ($this->userAttributesManager->removeByUser($userIdentifier)) { // @phpstan-ignore-line
            $io->info('The user is fully logged out.');

            return self::SUCCESS;
        }

        $io->warning('The user is not fully logged out. See logs for details.');

        return self::FAILURE;
    }
}
