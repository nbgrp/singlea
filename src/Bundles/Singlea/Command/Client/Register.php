<?php
// SPDX-License-Identifier: BSD-3-Clause

declare(strict_types=1);

namespace SingleA\Bundles\Singlea\Command\Client;

use SingleA\Bundles\Singlea\Command\QuestionHelperTrait;
use SingleA\Bundles\Singlea\Service\Client\RegistrationServiceInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'client:register',
    description: 'Register new client.',
)]
final class Register extends Command
{
    use QuestionHelperTrait;

    public function __construct(
        private readonly RegistrationServiceInterface $registerService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('registration-json', InputArgument::REQUIRED, 'JSON string with registration data.');
    }

    protected function interact(InputInterface $input, OutputInterface $output): void
    {
        if ($input->getArgument('registration-json')) {
            return;
        }

        $meta = stream_get_meta_data(\STDIN);

        /** @var string $json */
        $json = $meta['seekable']
            ? stream_get_contents(\STDIN)
            : $this->getQuestionHelper()->ask(
                $input,
                $output,
                (new Question("Enter the registration data JSON string (Ctrl+D for stop entering):\n"))
                    ->setMultiline(true),
            );

        $input->setArgument('registration-json', $json);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        /** @var string|null $json */
        $json = $input->getArgument('registration-json');
        if (empty($json)) {
            $io->error('The registration data JSON string does not specified.');

            return self::FAILURE;
        }

        try {
            $json = (array) json_decode($json, true, flags: \JSON_THROW_ON_ERROR);
            $registrationResult = $this->registerService->register($json);
        } catch (\JsonException $exception) {
            $io->error('Invalid JSON specified: '.$exception->getMessage());

            return self::FAILURE;
        } catch (\Throwable $exception) {
            $io->error($exception->getMessage());

            return self::FAILURE;
        }

        $data = [
            'client' => [
                'id' => $registrationResult->getClientId()->toBase58(),
                'secret' => $registrationResult->getSecret(),
            ],
        ];
        $data += $registrationResult->getOutput();

        $io->info('Client successfully registered.');
        $io->writeln(json_encode($data, \JSON_THROW_ON_ERROR | \JSON_PRETTY_PRINT), OutputInterface::OUTPUT_PLAIN); // @phan-suppress-current-line PhanPossiblyFalseTypeArgument

        return self::SUCCESS;
    }
}
