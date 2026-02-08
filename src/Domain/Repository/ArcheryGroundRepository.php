<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\ArcheryGround;
use App\Domain\Entity\ArcheryGround\ShootingLane;
use App\Domain\Entity\ArcheryGround\Target;

/**
 * Persistence boundary for archery grounds and their associated lanes/targets.
 */
interface ArcheryGroundRepository
{
    public function nextIdentity(): string;

    public function save(ArcheryGround $archeryGround): void;

    public function find(string $id): ArcheryGround|null;

    /** @return list<ArcheryGround> */
    public function findAll(): array;

    public function delete(string $id): void;

    public function addShootingLane(string $archeryGroundId, ShootingLane $lane): void;

    public function removeShootingLane(string $laneId): void;

    public function updateShootingLane(
        string $archeryGroundId,
        string $laneId,
        string $name,
        float $maxDistance,
    ): void;

    public function addTarget(string $archeryGroundId, Target $target): void;

    public function removeTarget(string $targetId): void;

    public function updateTarget(
        string $archeryGroundId,
        string $targetId,
        string $name,
        string $type,
        string|null $imagePath = null,
    ): void;
}
