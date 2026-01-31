<?php

declare(strict_types=1);

namespace App\Tests\Unit\Support;

use App\Application\Service\TargetImageStorage;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class SpyTargetImageStorage implements TargetImageStorage
{
    /** @var list<string> */
    public array $removed = [];

    /** @var list<array{targetId: string, path: string}> */
    public array $stored = [];

    public function store(UploadedFile $file, string $targetId): string
    {
        unset($file);

        $path = '/uploads/targets/' . $targetId . '.png';

        $this->stored[] = [
            'targetId' => $targetId,
            'path' => $path,
        ];

        return $path;
    }

    public function remove(string $path): void
    {
        $this->removed[] = $path;
    }
}
