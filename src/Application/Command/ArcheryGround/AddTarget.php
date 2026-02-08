<?php

declare(strict_types=1);

namespace App\Application\Command\ArcheryGround;

use App\Domain\ValueObject\TargetType;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final readonly class AddTarget
{
    public function __construct(
        public string $archeryGroundId,
        public TargetType $type,
        public string $name,
        public UploadedFile $image,
        public bool $forTrainingOnly = false,
        public string $notes = '',
    ) {
    }
}
