<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Migrations;

use Doctrine\DBAL\Schema\PrimaryKeyConstraint;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260208150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add attachments table for tournaments';
    }

    public function up(Schema $schema): void
    {
        $attachments = $schema->createTable('tournament_attachments');
        $attachments->addColumn('id', 'string', ['length' => 36]);
        $attachments->addColumn('tournament_id', 'string', ['length' => 36]);
        $attachments->addColumn('title', 'string', ['length' => 255]);
        $attachments->addColumn('file_path', 'string', ['length' => 255]);
        $attachments->addColumn('mime_type', 'string', ['length' => 100]);
        $attachments->addColumn('original_filename', 'string', ['length' => 255]);
        $attachments->addPrimaryKeyConstraint($this->primaryKey('id'));
        $attachments->addForeignKeyConstraint('tournaments', ['tournament_id'], ['id'], ['onDelete' => 'CASCADE']);
        $attachments->addIndex(['tournament_id'], 'idx_tournament_attachments');
    }

    public function down(Schema $schema): void
    {
        if ($schema->hasTable('tournament_attachments')) {
            $schema->dropTable('tournament_attachments');
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
