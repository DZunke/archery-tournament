<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Dbal\Hydrator;

use App\Domain\Entity\ArcheryGround\Target;
use App\Domain\ValueObject\TargetType;

final class TargetHydrator
{
    /** @param array{id: string, type: string, name: string, image: string, for_training_only: int|string|bool, notes: string} $row */
    public function hydrate(array $row): Target
    {
        return new Target(
            id: $row['id'],
            type: TargetType::from($row['type']),
            name: $row['name'],
            image: $row['image'],
            forTrainingOnly: (bool) $row['for_training_only'],
            notes: $row['notes'],
        );
    }
}
