<?php

declare(strict_types=1);

namespace App\Infrastructure\Storage;

use App\Application\Service\TargetImageStorage;
use RuntimeException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;

use function in_array;
use function str_starts_with;
use function strtolower;

final readonly class LocalTargetImageStorage implements TargetImageStorage
{
    public function __construct(
        private string $projectDir,
        private Filesystem $filesystem,
    ) {
    }

    public function store(UploadedFile $file, string $targetId): string
    {
        if (! $file->isValid()) {
            throw new RuntimeException('The uploaded image could not be processed.');
        }

        $guessedExtension  = $file->guessExtension();
        $extension         = $guessedExtension ?? $file->getClientOriginalExtension();
        $extension         = strtolower($extension);
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

        if ($extension === '' || ! in_array($extension, $allowedExtensions, true)) {
            throw new RuntimeException('Image must be JPG, PNG, WEBP, or GIF.');
        }

        $uploadDir = $this->projectDir . '/public/uploads/targets';
        $this->filesystem->mkdir($uploadDir);

        $fileName = $targetId . '.' . $extension;
        $file->move($uploadDir, $fileName);

        return '/uploads/targets/' . $fileName;
    }

    public function remove(string $path): void
    {
        if (! str_starts_with($path, '/uploads/targets/')) {
            return;
        }

        $absolutePath = $this->projectDir . '/public' . $path;
        $this->filesystem->remove($absolutePath);
    }
}
