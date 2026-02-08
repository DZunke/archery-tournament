<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260208130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add target_zone_size column to targets table';
    }

    public function up(Schema $schema): void
    {
        $targets = $schema->getTable('targets');
        $targets->addColumn('target_zone_size', 'integer', ['notnull' => false, 'default' => null]);
    }

    public function down(Schema $schema): void
    {
        $targets = $schema->getTable('targets');
        $targets->dropColumn('target_zone_size');
    }
}
