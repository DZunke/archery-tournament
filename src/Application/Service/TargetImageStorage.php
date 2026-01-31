<?php

declare(strict_types=1);

namespace App\Application\Service;

use Symfony\Component\HttpFoundation\File\UploadedFile;

interface TargetImageStorage
{
    public function store(UploadedFile $file, string $targetId): string;

    public function remove(string $path): void;
}
