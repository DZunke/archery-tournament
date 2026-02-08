<?php

declare(strict_types=1);

namespace App\Application\Query\ArcheryGround;

/**
 * Represents tournament usage information for lanes and targets.
 */
final readonly class TournamentUsages
{
    /**
     * @param array<string, list<array{id: string, name: string}>> $laneUsages   Mapping of lane ID to list of tournaments
     * @param array<string, list<array{id: string, name: string}>> $targetUsages Mapping of target ID to list of tournaments
     */
    public function __construct(
        public array $laneUsages,
        public array $targetUsages,
    ) {
    }

    /** @return list<array{id: string, name: string}> */
    public function tournamentsUsingLane(string $laneId): array
    {
        return $this->laneUsages[$laneId] ?? [];
    }

    /** @return list<array{id: string, name: string}> */
    public function tournamentsUsingTarget(string $targetId): array
    {
        return $this->targetUsages[$targetId] ?? [];
    }

    public function isLaneInUse(string $laneId): bool
    {
        return isset($this->laneUsages[$laneId]) && $this->laneUsages[$laneId] !== [];
    }

    public function isTargetInUse(string $targetId): bool
    {
        return isset($this->targetUsages[$targetId]) && $this->targetUsages[$targetId] !== [];
    }
}
