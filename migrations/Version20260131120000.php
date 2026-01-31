<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\PrimaryKeyConstraint;
use Doctrine\Migrations\AbstractMigration;

final class Version20260131120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Initial schema for archery grounds, tournaments, and assignments.';
    }

    public function up(Schema $schema): void
    {
        $archeryGrounds = $schema->createTable('archery_grounds');
        $archeryGrounds->addColumn('id', 'string', ['length' => 36]);
        $archeryGrounds->addColumn('name', 'string', ['length' => 255]);
        $archeryGrounds->addPrimaryKeyConstraint($this->primaryKey('id'));

        $shootingLanes = $schema->createTable('shooting_lanes');
        $shootingLanes->addColumn('id', 'string', ['length' => 36]);
        $shootingLanes->addColumn('archery_ground_id', 'string', ['length' => 36]);
        $shootingLanes->addColumn('name', 'string', ['length' => 255]);
        $shootingLanes->addColumn('max_distance', 'float');
        $shootingLanes->addPrimaryKeyConstraint($this->primaryKey('id'));
        $shootingLanes->addForeignKeyConstraint('archery_grounds', ['archery_ground_id'], ['id'], ['onDelete' => 'CASCADE']);
        $shootingLanes->addIndex(['archery_ground_id'], 'idx_shooting_lanes_ground');

        $targets = $schema->createTable('targets');
        $targets->addColumn('id', 'string', ['length' => 36]);
        $targets->addColumn('archery_ground_id', 'string', ['length' => 36]);
        $targets->addColumn('type', 'string', ['length' => 50]);
        $targets->addColumn('name', 'string', ['length' => 255]);
        $targets->addColumn('image', 'string', ['length' => 255]);
        $targets->addPrimaryKeyConstraint($this->primaryKey('id'));
        $targets->addForeignKeyConstraint('archery_grounds', ['archery_ground_id'], ['id'], ['onDelete' => 'CASCADE']);
        $targets->addIndex(['archery_ground_id'], 'idx_targets_ground');

        $tournaments = $schema->createTable('tournaments');
        $tournaments->addColumn('id', 'string', ['length' => 36]);
        $tournaments->addColumn('archery_ground_id', 'string', ['length' => 36]);
        $tournaments->addColumn('name', 'string', ['length' => 255]);
        $tournaments->addColumn('event_date', 'string', ['length' => 20]);
        $tournaments->addColumn('ruleset', 'string', ['length' => 50]);
        $tournaments->addColumn('number_of_targets', 'integer');
        $tournaments->addPrimaryKeyConstraint($this->primaryKey('id'));
        $tournaments->addForeignKeyConstraint('archery_grounds', ['archery_ground_id'], ['id'], ['onDelete' => 'CASCADE']);
        $tournaments->addIndex(['archery_ground_id'], 'idx_tournaments_ground');

        $assignments = $schema->createTable('tournament_targets');
        $assignments->addColumn('id', 'integer', ['autoincrement' => true]);
        $assignments->addColumn('tournament_id', 'string', ['length' => 36]);
        $assignments->addColumn('round', 'integer');
        $assignments->addColumn('shooting_lane_id', 'string', ['length' => 36]);
        $assignments->addColumn('target_id', 'string', ['length' => 36]);
        $assignments->addColumn('distance', 'integer');
        $assignments->addColumn('stakes', 'text');
        $assignments->addPrimaryKeyConstraint($this->primaryKey('id'));
        $assignments->addForeignKeyConstraint('tournaments', ['tournament_id'], ['id'], ['onDelete' => 'CASCADE']);
        $assignments->addForeignKeyConstraint('shooting_lanes', ['shooting_lane_id'], ['id'], ['onDelete' => 'CASCADE']);
        $assignments->addForeignKeyConstraint('targets', ['target_id'], ['id'], ['onDelete' => 'CASCADE']);
        $assignments->addIndex(['tournament_id'], 'idx_tournament_targets_tournament');
        $assignments->addIndex(['shooting_lane_id'], 'idx_tournament_targets_lane');
        $assignments->addIndex(['target_id'], 'idx_tournament_targets_target');
    }

    public function down(Schema $schema): void
    {
        if ($schema->hasTable('tournament_targets')) {
            $schema->dropTable('tournament_targets');
        }
        if ($schema->hasTable('tournaments')) {
            $schema->dropTable('tournaments');
        }
        if ($schema->hasTable('targets')) {
            $schema->dropTable('targets');
        }
        if ($schema->hasTable('shooting_lanes')) {
            $schema->dropTable('shooting_lanes');
        }
        if ($schema->hasTable('archery_grounds')) {
            $schema->dropTable('archery_grounds');
        }
    }

    private function primaryKey(string $column, string ...$columns): PrimaryKeyConstraint
    {
        return PrimaryKeyConstraint::editor()
            ->setUnquotedColumnNames($column, ...$columns)
            ->setIsClustered(false)
            ->create();
    }
}
