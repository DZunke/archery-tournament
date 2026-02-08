<?php

declare(strict_types=1);

namespace App\Application\Command\ArcheryGround;

use App\Domain\ValueObject\TargetType;
use App\Domain\ValueObject\TargetZoneSizeConverter;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final readonly class AddTarget
{
    public TargetType $type;

    public function __construct(
        public string $archeryGroundId,
        TargetType $type,
        public string $name,
        public UploadedFile $image,
        public bool $forTrainingOnly = false,
        public string $notes = '',
        public int|null $targetZoneSize = null,
    ) {
        // When zone size is provided, derive the type from it
        $this->type = $this->targetZoneSize !== null
            ? TargetZoneSizeConverter::toTargetType($this->targetZoneSize)
            : $type;
    }
}
