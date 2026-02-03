<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Migrations;

use Doctrine\DBAL\Schema\PrimaryKeyConstraint;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260203120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add users and user assignments for tournaments and archery grounds.';
    }

    public function up(Schema $schema): void
    {
        $users = $schema->createTable('users');
        $users->addColumn('id', 'string', ['length' => 36]);
        $users->addColumn('username', 'string', ['length' => 120]);
        $users->addColumn('password', 'string', ['length' => 255]);
        $users->addColumn('roles', 'text');
        $users->addPrimaryKeyConstraint($this->primaryKey('id'));
        $users->addUniqueIndex(['username'], 'uniq_users_username');

        $userTournaments = $schema->createTable('user_tournaments');
        $userTournaments->addColumn('user_id', 'string', ['length' => 36]);
        $userTournaments->addColumn('tournament_id', 'string', ['length' => 36]);
        $userTournaments->addPrimaryKeyConstraint($this->primaryKey('user_id', 'tournament_id'));
        $userTournaments->addForeignKeyConstraint('users', ['user_id'], ['id'], ['onDelete' => 'CASCADE']);
        $userTournaments->addForeignKeyConstraint('tournaments', ['tournament_id'], ['id'], ['onDelete' => 'CASCADE']);
        $userTournaments->addIndex(['user_id'], 'idx_user_tournaments_user');
        $userTournaments->addIndex(['tournament_id'], 'idx_user_tournaments_tournament');

        $userArcheryGrounds = $schema->createTable('user_archery_grounds');
        $userArcheryGrounds->addColumn('user_id', 'string', ['length' => 36]);
        $userArcheryGrounds->addColumn('archery_ground_id', 'string', ['length' => 36]);
        $userArcheryGrounds->addPrimaryKeyConstraint($this->primaryKey('user_id', 'archery_ground_id'));
        $userArcheryGrounds->addForeignKeyConstraint('users', ['user_id'], ['id'], ['onDelete' => 'CASCADE']);
        $userArcheryGrounds->addForeignKeyConstraint('archery_grounds', ['archery_ground_id'], ['id'], ['onDelete' => 'CASCADE']);
        $userArcheryGrounds->addIndex(['user_id'], 'idx_user_archery_grounds_user');
        $userArcheryGrounds->addIndex(['archery_ground_id'], 'idx_user_archery_grounds_ground');
    }

    public function down(Schema $schema): void
    {
        if ($schema->hasTable('user_archery_grounds')) {
            $schema->dropTable('user_archery_grounds');
        }

        if ($schema->hasTable('user_tournaments')) {
            $schema->dropTable('user_tournaments');
        }

        if ($schema->hasTable('users')) {
            $schema->dropTable('users');
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
