<?php

declare(strict_types=1);

namespace App\Application\Command\Tournament;

use Symfony\Component\HttpFoundation\File\UploadedFile;

final readonly class AddAttachment
{
    public function __construct(
        public string $tournamentId,
        public string $title,
        public UploadedFile $file,
    ) {
    }
}
