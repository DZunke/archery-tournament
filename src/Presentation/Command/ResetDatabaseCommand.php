<?php

declare(strict_types=1);

namespace App\Presentation\Command;

use App\Infrastructure\Persistence\Dbal\DatabaseSchemaManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:db:reset',
    description: 'Drops and recreates the database schema.',
)]
final class ResetDatabaseCommand extends Command
{
    public function __construct(private readonly DatabaseSchemaManager $schemaManager)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('force', 'f', InputOption::VALUE_NONE, 'Skip confirmation prompt.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io    = new SymfonyStyle($input, $output);
        $force = (bool) $input->getOption('force');

        if (! $force && ! $io->confirm('This will delete all data. Continue?', false)) {
            $io->warning('Database reset aborted.');

            return Command::SUCCESS;
        }

        $this->schemaManager->reset();

        $io->success('Database reset complete.');

        return Command::SUCCESS;
    }
}
