<?php

declare(strict_types=1);

namespace App\Application\Command\ArcheryGround;

use Symfony\Component\HttpFoundation\File\UploadedFile;

final readonly class AddTarget
{
    public function __construct(
        public string $archeryGroundId,
        public string $type,
        public string $name,
        public UploadedFile|null $image,
    ) {
    }
}
