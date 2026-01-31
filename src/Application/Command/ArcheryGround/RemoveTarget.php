<?php

declare(strict_types=1);

namespace App\Application\Command\ArcheryGround;

final readonly class RemoveTarget
{
    public function __construct(
        public string $archeryGroundId,
        public string $targetId,
    ) {
    }
}
