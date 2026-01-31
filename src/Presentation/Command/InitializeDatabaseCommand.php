<?php

declare(strict_types=1);

namespace App\Presentation\Command;

use Doctrine\DBAL\Connection;
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
    public function __construct(private readonly Connection $connection)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $statements = [
            'CREATE TABLE IF NOT EXISTS archery_grounds (
                id VARCHAR(36) PRIMARY KEY,
                name VARCHAR(255) NOT NULL
            )',
            'CREATE TABLE IF NOT EXISTS shooting_lanes (
                id VARCHAR(36) PRIMARY KEY,
                archery_ground_id VARCHAR(36) NOT NULL,
                name VARCHAR(255) NOT NULL,
                max_distance NUMERIC NOT NULL,
                FOREIGN KEY (archery_ground_id) REFERENCES archery_grounds(id) ON DELETE CASCADE
            )',
            'CREATE TABLE IF NOT EXISTS targets (
                id VARCHAR(36) PRIMARY KEY,
                archery_ground_id VARCHAR(36) NOT NULL,
                type VARCHAR(50) NOT NULL,
                name VARCHAR(255) NOT NULL,
                image VARCHAR(255) NOT NULL,
                FOREIGN KEY (archery_ground_id) REFERENCES archery_grounds(id) ON DELETE CASCADE
            )',
            'CREATE INDEX IF NOT EXISTS idx_shooting_lanes_ground ON shooting_lanes (archery_ground_id)',
            'CREATE INDEX IF NOT EXISTS idx_targets_ground ON targets (archery_ground_id)',
        ];

        foreach ($statements as $statement) {
            $this->connection->executeStatement($statement);
        }

        $io->success('Database schema initialized.');

        return Command::SUCCESS;
    }
}
