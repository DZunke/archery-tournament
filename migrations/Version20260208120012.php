<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260208120012 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add for_training_only and notes columns to targets table';
    }

    public function up(Schema $schema): void
    {
        $shootingLanes = $schema->getTable('targets');
        $shootingLanes->addColumn('for_training_only', 'boolean', ['default' => false]);
        $shootingLanes->addColumn('notes', 'text', ['default' => '']);
    }

    public function down(Schema $schema): void
    {
        $shootingLanes = $schema->getTable('targets');
        $shootingLanes->dropColumn('notes');
        $shootingLanes->dropColumn('for_training_only');
    }
}
