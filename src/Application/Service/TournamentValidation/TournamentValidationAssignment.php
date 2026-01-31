<?php

declare(strict_types=1);

namespace App\Application\Service\TournamentValidation;

use App\Domain\Entity\ArcheryGround\ShootingLane;
use App\Domain\Entity\ArcheryGround\Target;

final readonly class TournamentValidationAssignment
{
    /** @param array<string,int> $stakes */
    public function __construct(
        public int $round,
        public ShootingLane|null $lane,
        public Target|null $target,
        public array $stakes,
        public int|null $row = null,
    ) {
    }
}
