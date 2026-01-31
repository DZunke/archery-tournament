<?php

declare(strict_types=1);

namespace App\Presentation\Command;

use App\Infrastructure\Persistence\DatabaseMigrator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

use function count;
use function is_int;

#[AsCommand(
    name: 'app:db:init',
    description: 'Initializes the database schema using migrations.',
)]
final class InitializeDatabaseCommand extends Command
{
    public function __construct(private readonly DatabaseMigrator $databaseMigrator)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $plan = $this->databaseMigrator->migrate('latest');
        if (is_int($plan) || count($plan) === 0) {
            $io->success('Database schema is already up to date.');

            return Command::SUCCESS;
        }

        $io->success('Database schema initialized via migrations.');

        return Command::SUCCESS;
    }
}
