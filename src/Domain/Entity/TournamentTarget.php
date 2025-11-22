<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\Entity\ArcheryGround\ShootingLane;
use App\Domain\Entity\ArcheryGround\Target;
use App\Domain\ValueObject\StakeDistances;
use Webmozart\Assert\Assert;

/**
 * Represents an assigned target on a lane for a specific round, including per-stake distances.
 */
final class TournamentTarget
{
    public function __construct(
        private readonly int $round,
        private readonly ShootingLane $shootingLane,
        private readonly Target $target,
        private readonly int $distance,
        private readonly StakeDistances $stakes,
    ) {
        Assert::greaterThan($this->round, 0, 'Round must be positive.');
        Assert::true($this->stakes->count() > 0, 'Stakes must not be empty.');
    }

    public function round(): int
    {
        return $this->round;
    }

    public function shootingLane(): ShootingLane
    {
        return $this->shootingLane;
    }

    public function target(): Target
    {
        return $this->target;
    }

    public function distance(): int
    {
        return $this->distance;
    }

    public function stakes(): StakeDistances
    {
        return $this->stakes;
    }
}
