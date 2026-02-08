<?php

declare(strict_types=1);

namespace App\Tests\Unit\Support;

use App\Application\Service\AttachmentStorage;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class SpyAttachmentStorage implements AttachmentStorage
{
    /** @var list<string> */
    public array $removed = [];

    /** @var list<array{attachmentId: string, path: string}> */
    public array $stored = [];

    public function store(UploadedFile $file, string $attachmentId): string
    {
        $extension = $file->guessExtension() ?? 'pdf';

        $path = '/uploads/attachments/' . $attachmentId . '.' . $extension;

        $this->stored[] = [
            'attachmentId' => $attachmentId,
            'path' => $path,
        ];

        return $path;
    }

    public function remove(string $path): void
    {
        $this->removed[] = $path;
    }
}
