<?php

declare(strict_types=1);

namespace App\Application\Service;

use Symfony\Component\HttpFoundation\File\UploadedFile;

interface AttachmentStorage
{
    /**
     * Store an uploaded file and return the relative public path.
     *
     * @return string The relative URL path to the stored file (e.g., /uploads/attachments/xxx.pdf)
     */
    public function store(UploadedFile $file, string $attachmentId): string;

    /**
     * Remove a previously stored attachment.
     */
    public function remove(string $path): void;
}
