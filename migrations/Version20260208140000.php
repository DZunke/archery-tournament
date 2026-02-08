<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Migrations;

use Doctrine\DBAL\Schema\PrimaryKeyConstraint;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260208140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add attachments table for archery grounds';
    }

    public function up(Schema $schema): void
    {
        $attachments = $schema->createTable('archery_ground_attachments');
        $attachments->addColumn('id', 'string', ['length' => 36]);
        $attachments->addColumn('archery_ground_id', 'string', ['length' => 36]);
        $attachments->addColumn('title', 'string', ['length' => 255]);
        $attachments->addColumn('file_path', 'string', ['length' => 255]);
        $attachments->addColumn('mime_type', 'string', ['length' => 100]);
        $attachments->addColumn('original_filename', 'string', ['length' => 255]);
        $attachments->addPrimaryKeyConstraint($this->primaryKey('id'));
        $attachments->addForeignKeyConstraint('archery_grounds', ['archery_ground_id'], ['id'], ['onDelete' => 'CASCADE']);
        $attachments->addIndex(['archery_ground_id'], 'idx_attachments_ground');
    }

    public function down(Schema $schema): void
    {
        if ($schema->hasTable('archery_ground_attachments')) {
            $schema->dropTable('archery_ground_attachments');
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
