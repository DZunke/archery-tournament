<?php

declare(strict_types=1);

namespace App\Infrastructure\Storage;

use App\Application\Service\AttachmentStorage;
use RuntimeException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;

use function in_array;
use function str_starts_with;
use function strtolower;

final readonly class LocalAttachmentStorage implements AttachmentStorage
{
    private const array ALLOWED_PDF    = ['pdf'];
    private const array ALLOWED_IMAGES = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

    public function __construct(
        private string $projectDir,
        private Filesystem $filesystem,
    ) {
    }

    public function store(UploadedFile $file, string $attachmentId): string
    {
        if (! $file->isValid()) {
            throw new RuntimeException('The uploaded file could not be processed.');
        }

        $guessedExtension  = $file->guessExtension();
        $extension         = $guessedExtension ?? $file->getClientOriginalExtension();
        $extension         = strtolower($extension);
        $allowedExtensions = [...self::ALLOWED_PDF, ...self::ALLOWED_IMAGES];

        if ($extension === '' || ! in_array($extension, $allowedExtensions, true)) {
            throw new RuntimeException('Attachment must be PDF, JPG, PNG, WEBP, or GIF.');
        }

        $uploadDir = $this->projectDir . '/public/uploads/attachments';
        $this->filesystem->mkdir($uploadDir);

        $fileName = $attachmentId . '.' . $extension;
        $file->move($uploadDir, $fileName);

        return '/uploads/attachments/' . $fileName;
    }

    public function remove(string $path): void
    {
        if (! str_starts_with($path, '/uploads/attachments/')) {
            return;
        }

        $absolutePath = $this->projectDir . '/public' . $path;
        $this->filesystem->remove($absolutePath);
    }
}
