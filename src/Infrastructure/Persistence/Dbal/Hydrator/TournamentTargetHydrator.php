<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Dbal\Hydrator;

use App\Domain\Entity\ArcheryGround\ShootingLane;
use App\Domain\Entity\ArcheryGround\Target;
use App\Domain\Entity\TournamentTarget;
use App\Domain\ValueObject\StakeDistances;

final class TournamentTargetHydrator
{
    /** @param array{round: int|string, distance: int|string, stakes: array<string,int>} $row */
    public function hydrate(array $row, ShootingLane $lane, Target $target): TournamentTarget
    {
        return new TournamentTarget(
            round: (int) $row['round'],
            shootingLane: $lane,
            target: $target,
            distance: (int) $row['distance'],
            stakes: new StakeDistances($row['stakes']),
        );
    }
}
