<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260208120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add training flag and notes fields to shooting lanes.';
    }

    public function up(Schema $schema): void
    {
        $shootingLanes = $schema->getTable('shooting_lanes');
        $shootingLanes->addColumn('for_training_only', 'boolean', ['default' => false]);
        $shootingLanes->addColumn('notes', 'text', ['default' => '']);
    }

    public function down(Schema $schema): void
    {
        $shootingLanes = $schema->getTable('shooting_lanes');
        $shootingLanes->dropColumn('notes');
        $shootingLanes->dropColumn('for_training_only');
    }
}
