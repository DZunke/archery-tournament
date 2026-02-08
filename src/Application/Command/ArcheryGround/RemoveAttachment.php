<?php

declare(strict_types=1);

namespace App\Application\Command\ArcheryGround;

final readonly class RemoveAttachment
{
    public function __construct(
        public string $archeryGroundId,
        public string $attachmentId,
    ) {
    }
}
