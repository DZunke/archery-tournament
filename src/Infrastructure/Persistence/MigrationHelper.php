<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use Doctrine\DBAL\Connection;
use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\Configuration\Connection\ExistingConnection;
use Doctrine\Migrations\Configuration\Migration\ExistingConfiguration;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\Exception\NoMigrationsFoundWithCriteria;
use Doctrine\Migrations\Exception\NoMigrationsToExecute;
use Doctrine\Migrations\Exception\UnknownMigrationVersion;
use Doctrine\Migrations\Metadata\MigrationPlanList;
use Doctrine\Migrations\MigratorConfiguration;
use Symfony\Component\Console\Command\Command;

class MigrationHelper
{
    public function getDependencyFactory(
        Connection $connection,
        string $namespace,
        string $directory,
    ): DependencyFactory {
        $migrationConfiguration = new ExistingConfiguration($this->getConfiguration($namespace, $directory));
        $migrationConnection    = new ExistingConnection($connection);

        $dependencyFactory = DependencyFactory::fromConnection($migrationConfiguration, $migrationConnection);

        $dependencyFactory->getMetadataStorage()->ensureInitialized();
        $dependencyFactory->getMigrationRepository();

        return $dependencyFactory;
    }

    public function calculateMigrationPlan(
        DependencyFactory $dependencyFactory,
        string $versionAlias,
    ): MigrationPlanList|int {
        try {
            $version = $dependencyFactory->getVersionAliasResolver()->resolveVersionAlias($versionAlias);
        } catch (UnknownMigrationVersion) {
            return Command::INVALID;
        } catch (NoMigrationsToExecute | NoMigrationsFoundWithCriteria) {
            return Command::SUCCESS;
        }

        return $dependencyFactory->getMigrationPlanCalculator()->getPlanUntilVersion($version);
    }

    public function migrate(DependencyFactory $dependencyFactory, MigrationPlanList $plan): void
    {
        $migratorConfiguration = new MigratorConfiguration();

        $migrator = $dependencyFactory->getMigrator();
        $migrator->migrate($plan, $migratorConfiguration);
    }

    protected function getConfiguration(string $namespace, string $directory): Configuration
    {
        $configuration = new Configuration();
        $configuration->setAllOrNothing(true);
        $configuration->addMigrationsDirectory($namespace, $directory);

        return $configuration;
    }
}
