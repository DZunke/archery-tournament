<?php

declare(strict_types=1);

namespace App\Presentation\Input\ArcheryGround;

use App\Application\Command\ArcheryGround\AddAttachment;
use LogicException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;

use function in_array;
use function strtolower;
use function trim;

final readonly class AddAttachmentInput
{
    private const array ALLOWED_MIME_TYPES = [
        'application/pdf',
        'image/jpeg',
        'image/png',
        'image/webp',
        'image/gif',
    ];

    public function __construct(
        public string $title,
        public UploadedFile|null $file,
    ) {
    }

    public static function fromRequest(Request $request): self
    {
        return new self(
            (string) $request->request->get('title', ''),
            $request->files->get('file'),
        );
    }

    /** @return list<string> */
    public function errors(): array
    {
        $errors = [];

        if (trim($this->title) === '') {
            $errors[] = 'Attachment title is required.';
        }

        if (! $this->file instanceof UploadedFile) {
            $errors[] = 'Please upload a file.';
        } elseif (! $this->file->isValid()) {
            $errors[] = 'The uploaded file could not be processed.';
        } else {
            $mimeType = strtolower($this->file->getMimeType() ?? '');
            if (! in_array($mimeType, self::ALLOWED_MIME_TYPES, true)) {
                $errors[] = 'File must be PDF, JPG, PNG, WEBP, or GIF.';
            }
        }

        return $errors;
    }

    public function toCommand(string $archeryGroundId): AddAttachment
    {
        if (! $this->file instanceof UploadedFile) {
            throw new LogicException('Cannot create command without a valid file.');
        }

        return new AddAttachment(
            archeryGroundId: $archeryGroundId,
            title: trim($this->title),
            file: $this->file,
        );
    }
}
