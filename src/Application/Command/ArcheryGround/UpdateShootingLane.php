<?php

declare(strict_types=1);

namespace App\Application\Command\ArcheryGround;

final readonly class UpdateShootingLane
{
    public function __construct(
        public string $archeryGroundId,
        public string $laneId,
        public string $name,
        public float $maxDistance,
    ) {
    }
}
