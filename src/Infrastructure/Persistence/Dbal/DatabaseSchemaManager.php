<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Dbal;

use Doctrine\DBAL\Connection;

final readonly class DatabaseSchemaManager
{
    public function __construct(private Connection $connection)
    {
    }

    public function initialize(): void
    {
        foreach ($this->schemaStatements() as $statement) {
            $this->connection->executeStatement($statement);
        }
    }

    public function reset(): void
    {
        foreach ($this->dropStatements() as $statement) {
            $this->connection->executeStatement($statement);
        }

        $this->initialize();
    }

    /** @return list<string> */
    private function schemaStatements(): array
    {
        return [
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
    }

    /** @return list<string> */
    private function dropStatements(): array
    {
        return [
            'DROP TABLE IF EXISTS targets',
            'DROP TABLE IF EXISTS shooting_lanes',
            'DROP TABLE IF EXISTS archery_grounds',
        ];
    }
}
