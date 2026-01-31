<?php

declare(strict_types=1);

namespace App\Presentation\Command;

use App\Infrastructure\Persistence\DatabaseMigrator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

use function count;
use function is_int;

#[AsCommand(
    name: 'app:db:migrate',
    description: 'Executes database migrations.',
)]
final class MigrateDatabaseCommand extends Command
{
    public function __construct(private readonly DatabaseMigrator $databaseMigrator)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('to', null, InputOption::VALUE_REQUIRED, 'Version alias to migrate to.', 'latest');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io      = new SymfonyStyle($input, $output);
        $version = (string) $input->getOption('to');

        $plan = $this->databaseMigrator->migrate($version);

        if (is_int($plan) || count($plan) === 0) {
            $io->success('The database is already at the requested version.');

            return Command::SUCCESS;
        }

        $io->success('Database migrations executed.');

        return Command::SUCCESS;
    }
}
