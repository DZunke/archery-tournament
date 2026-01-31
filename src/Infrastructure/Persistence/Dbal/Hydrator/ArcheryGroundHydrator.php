<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Dbal\Hydrator;

use App\Domain\Entity\ArcheryGround;
use App\Domain\Entity\ArcheryGround\ShootingLane;
use App\Domain\Entity\ArcheryGround\Target;

final class ArcheryGroundHydrator
{
    /**
     * @param array{id: string, name: string} $row
     * @param list<ShootingLane>              $lanes
     * @param list<Target>                    $targets
     */
    public function hydrate(array $row, array $lanes, array $targets): ArcheryGround
    {
        return new ArcheryGround(
            id: $row['id'],
            name: $row['name'],
            targetStorage: $targets,
            shootingLanes: $lanes,
        );
    }
}
