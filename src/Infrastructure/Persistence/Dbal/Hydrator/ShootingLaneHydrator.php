<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Dbal\Hydrator;

use App\Domain\Entity\ArcheryGround\ShootingLane;

final class ShootingLaneHydrator
{
    /** @param array{id: string, name: string, max_distance: float|string, for_training_only: int|string|bool, notes: string} $row */
    public function hydrate(array $row): ShootingLane
    {
        return new ShootingLane(
            id: $row['id'],
            name: $row['name'],
            maxDistance: (float) $row['max_distance'],
            forTrainingOnly: (bool) $row['for_training_only'],
            notes: $row['notes'],
        );
    }
}
