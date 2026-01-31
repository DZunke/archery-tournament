<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\MariaDBPlatform;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Platforms\SQLitePlatform;
use Doctrine\Migrations\Metadata\MigrationPlanList;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

use function array_reverse;
use function count;
use function is_int;

final readonly class DatabaseMigrator
{
    private const string MIGRATION_NAMESPACE = 'App\\Infrastructure\\Persistence\\Migrations';
    private const string MIGRATION_DIRECTORY = '/migrations';

    public function __construct(
        private Connection $connection,
        private MigrationHelper $migrationHelper,
        #[Autowire('%kernel.project_dir%')]
        private string $projectDir,
    ) {
    }

    public function migrate(string $versionAlias = 'latest'): MigrationPlanList|int
    {
        $dependencyFactory = $this->migrationHelper->getDependencyFactory(
            $this->connection,
            self::MIGRATION_NAMESPACE,
            $this->projectDir . self::MIGRATION_DIRECTORY,
        );

        $plan = $this->migrationHelper->calculateMigrationPlan($dependencyFactory, $versionAlias);

        if (is_int($plan) || count($plan) === 0) {
            return $plan;
        }

        $this->migrationHelper->migrate($dependencyFactory, $plan);

        return $plan;
    }

    public function reset(): MigrationPlanList|int
    {
        $this->dropAllTables();

        return $this->migrate('latest');
    }

    private function dropAllTables(): void
    {
        $platform = $this->connection->getDatabasePlatform();

        if ($platform instanceof PostgreSQLPlatform) {
            $this->connection->executeStatement('DROP SCHEMA public CASCADE');
            $this->connection->executeStatement('CREATE SCHEMA public');

            return;
        }

        if ($platform instanceof MySQLPlatform || $platform instanceof MariaDBPlatform) {
            $this->connection->executeStatement('SET FOREIGN_KEY_CHECKS=0');
        } elseif ($platform instanceof SQLitePlatform) {
            $this->connection->executeStatement('PRAGMA foreign_keys = OFF');
        }

        $schemaManager = $this->connection->createSchemaManager();
        foreach (array_reverse($schemaManager->listTableNames()) as $table) {
            $schemaManager->dropTable($table);
        }

        if ($platform instanceof MySQLPlatform || $platform instanceof MariaDBPlatform) {
            $this->connection->executeStatement('SET FOREIGN_KEY_CHECKS=1');
        } elseif ($platform instanceof SQLitePlatform) {
            $this->connection->executeStatement('PRAGMA foreign_keys = ON');
        }
    }
}
