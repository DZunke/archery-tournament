<?php

declare(strict_types=1);

namespace App\Application\Command\ArcheryGround;

use App\Domain\ValueObject\TargetType;
use App\Domain\ValueObject\TargetZoneSizeConverter;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final readonly class UpdateTarget
{
    public TargetType $type;

    public function __construct(
        public string $archeryGroundId,
        public string $targetId,
        public string $name,
        TargetType $type,
        public bool $forTrainingOnly = false,
        public string $notes = '',
        public UploadedFile|null $image = null,
        public int|null $targetZoneSize = null,
    ) {
        // When zone size is provided, derive the type from it
        $this->type = $this->targetZoneSize !== null
            ? TargetZoneSizeConverter::toTargetType($this->targetZoneSize)
            : $type;
    }
}
