<?php

declare(strict_types=1);

namespace App\Application\Service\TournamentValidation;

use App\Application\Command\Tournament\TournamentTargetAssignment;
use App\Domain\Entity\ArcheryGround\ShootingLane;
use App\Domain\Entity\ArcheryGround\Target;
use App\Domain\Entity\Tournament;
use App\Domain\ValueObject\Ruleset;

use function count;

final readonly class TournamentValidationContext
{
    /** @param list<TournamentValidationAssignment> $assignments */
    public function __construct(
        public Ruleset $ruleset,
        public int $expectedTargetCount,
        public array $assignments,
    ) {
    }

    public static function fromTournament(Tournament $tournament): self
    {
        $assignments = [];

        foreach ($tournament->targets() as $assignment) {
            $assignments[] = new TournamentValidationAssignment(
                round: $assignment->round(),
                lane: $assignment->shootingLane(),
                target: $assignment->target(),
                stakes: $assignment->stakes()->all(),
            );
        }

        return new self(
            ruleset: $tournament->ruleset(),
            expectedTargetCount: $tournament->numberOfTargets(),
            assignments: $assignments,
        );
    }

    /**
     * @param list<TournamentTargetAssignment> $assignments
     * @param list<ShootingLane>               $lanes
     * @param list<Target>                     $targets
     */
    public static function fromDraftAssignments(
        Ruleset $ruleset,
        int $expectedTargetCount,
        array $assignments,
        array $lanes,
        array $targets,
    ): self {
        $laneMap   = self::buildLaneMap($lanes);
        $targetMap = self::buildTargetMap($targets);
        $drafts    = [];

        foreach ($assignments as $assignment) {
            $drafts[] = new TournamentValidationAssignment(
                round: $assignment->round,
                lane: $laneMap[$assignment->shootingLaneId] ?? null,
                target: $targetMap[$assignment->targetId] ?? null,
                stakes: $assignment->stakes,
                row: $assignment->rowIndex + 1,
            );
        }

        return new self(
            ruleset: $ruleset,
            expectedTargetCount: $expectedTargetCount,
            assignments: $drafts,
        );
    }

    public function assignmentCount(): int
    {
        return count($this->assignments);
    }

    /**
     * @param list<ShootingLane> $lanes
     *
     * @return array<string,ShootingLane>
     */
    private static function buildLaneMap(array $lanes): array
    {
        $map = [];
        foreach ($lanes as $lane) {
            $map[$lane->id()] = $lane;
        }

        return $map;
    }

    /**
     * @param list<Target> $targets
     *
     * @return array<string,Target>
     */
    private static function buildTargetMap(array $targets): array
    {
        $map = [];
        foreach ($targets as $target) {
            $map[$target->id()] = $target;
        }

        return $map;
    }
}
