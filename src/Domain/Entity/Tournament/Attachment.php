<?php

declare(strict_types=1);

namespace App\Domain\Entity\Tournament;

use Webmozart\Assert\Assert;

use function str_starts_with;

final class Attachment
{
    public function __construct(
        private readonly string $id,
        private readonly string $title,
        private readonly string $filePath,
        private readonly string $mimeType,
        private readonly string $originalFilename,
    ) {
        Assert::uuid($this->id, 'The attachment id must be a valid UUID.');
        Assert::notEmpty($this->title, 'The attachment title must not be empty.');
        Assert::notEmpty($this->filePath, 'The attachment file path must not be empty.');
        Assert::notEmpty($this->mimeType, 'The attachment MIME type must not be empty.');
        Assert::notEmpty($this->originalFilename, 'The original filename must not be empty.');
    }

    public function id(): string
    {
        return $this->id;
    }

    public function title(): string
    {
        return $this->title;
    }

    public function filePath(): string
    {
        return $this->filePath;
    }

    public function mimeType(): string
    {
        return $this->mimeType;
    }

    public function originalFilename(): string
    {
        return $this->originalFilename;
    }

    public function isImage(): bool
    {
        return str_starts_with($this->mimeType, 'image/');
    }

    public function isPdf(): bool
    {
        return $this->mimeType === 'application/pdf';
    }
}
