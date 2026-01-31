<?php

declare(strict_types=1);

namespace App\Presentation\Command;

use App\Infrastructure\Persistence\Dbal\DatabaseSchemaManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:db:init',
    description: 'Initializes the database schema for archery grounds.',
)]
final class InitializeDatabaseCommand extends Command
{
    public function __construct(private readonly DatabaseSchemaManager $schemaManager)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $this->schemaManager->initialize();

        $io->success('Database schema initialized.');

        return Command::SUCCESS;
    }
}
