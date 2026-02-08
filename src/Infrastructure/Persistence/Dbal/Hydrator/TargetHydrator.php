<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Dbal\Hydrator;

use App\Domain\Entity\ArcheryGround\Target;
use App\Domain\ValueObject\TargetType;

final class TargetHydrator
{
    /** @param array{id: string, type: string, name: string, image: string, for_training_only: int|string|bool, notes: string, target_zone_size: int|string|null} $row */
    public function hydrate(array $row): Target
    {
        return new Target(
            id: $row['id'],
            type: TargetType::from($row['type']),
            name: $row['name'],
            image: $row['image'],
            forTrainingOnly: (bool) $row['for_training_only'],
            notes: $row['notes'],
            targetZoneSize: $row['target_zone_size'] !== null ? (int) $row['target_zone_size'] : null,
        );
    }
}
