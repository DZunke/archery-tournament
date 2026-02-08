<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Dbal\Hydrator;

use App\Domain\Entity\ArcheryGround\Attachment;

final class AttachmentHydrator
{
    /** @param array{id: string, title: string, file_path: string, mime_type: string, original_filename: string} $row */
    public function hydrate(array $row): Attachment
    {
        return new Attachment(
            id: $row['id'],
            title: $row['title'],
            filePath: $row['file_path'],
            mimeType: $row['mime_type'],
            originalFilename: $row['original_filename'],
        );
    }
}
