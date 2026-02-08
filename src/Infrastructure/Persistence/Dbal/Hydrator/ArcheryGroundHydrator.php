<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Dbal\Hydrator;

use App\Domain\Entity\ArcheryGround;
use App\Domain\Entity\ArcheryGround\Attachment;
use App\Domain\Entity\ArcheryGround\ShootingLane;
use App\Domain\Entity\ArcheryGround\Target;

final class ArcheryGroundHydrator
{
    /**
     * @param array{id: string, name: string} $row
     * @param list<ShootingLane>              $lanes
     * @param list<Target>                    $targets
     * @param list<Attachment>                $attachments
     */
    public function hydrate(array $row, array $lanes, array $targets, array $attachments = []): ArcheryGround
    {
        return new ArcheryGround(
            id: $row['id'],
            name: $row['name'],
            targetStorage: $targets,
            shootingLanes: $lanes,
            attachments: $attachments,
        );
    }
}
