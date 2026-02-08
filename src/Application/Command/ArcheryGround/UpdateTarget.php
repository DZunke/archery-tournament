<?php

declare(strict_types=1);

namespace App\Application\Command\ArcheryGround;

use App\Domain\ValueObject\TargetType;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final readonly class UpdateTarget
{
    public function __construct(
        public string $archeryGroundId,
        public string $targetId,
        public string $name,
        public TargetType $type,
        public UploadedFile|null $image = null,
    ) {
    }
}
