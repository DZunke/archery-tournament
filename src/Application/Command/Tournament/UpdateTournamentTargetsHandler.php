<?php

declare(strict_types=1);

namespace App\Application\Command\Tournament;

use App\Application\Command\CommandResult;
use App\Domain\Entity\ArcheryGround\ShootingLane;
use App\Domain\Entity\ArcheryGround\Target;
use App\Domain\Entity\Tournament;
use App\Domain\Entity\TournamentTarget;
use App\Domain\Entity\TournamentTargetCollection;
use App\Domain\Repository\TournamentRepository;
use App\Domain\ValueObject\StakeDistances;

use function min;

final readonly class UpdateTournamentTargetsHandler
{
    public function __construct(private TournamentRepository $tournamentRepository)
    {
    }

    public function __invoke(UpdateTournamentTargets $command): CommandResult
    {
        $tournament = $this->tournamentRepository->find($command->tournamentId);
        if (! $tournament instanceof Tournament) {
            return CommandResult::failure('Tournament not found.');
        }

        $archeryGround = $tournament->archeryGround();
        $laneMap       = $this->buildLaneMap($archeryGround->shootingLanes());
        $targetMap     = $this->buildTargetMap($archeryGround->targetStorage());
        $ruleset       = $tournament->ruleset();

        $collection = new TournamentTargetCollection();

        foreach ($command->assignments as $assignment) {
            if ($assignment->round <= 0) {
                return CommandResult::failure('Round must be greater than zero.');
            }

            $lane   = $laneMap[$assignment->shootingLaneId] ?? null;
            $target = $targetMap[$assignment->targetId] ?? null;

            if (! $lane instanceof ShootingLane) {
                return CommandResult::failure('Invalid shooting lane selected.');
            }

            if (! $target instanceof Target) {
                return CommandResult::failure('Invalid target selected.');
            }

            $targetType = $target->type();
            $ranges     = $ruleset->stakeDistanceRanges($targetType);
            $stakes     = [];

            foreach ($ranges as $stake => $range) {
                if (! isset($assignment->stakes[$stake])) {
                    return CommandResult::failure('Missing stake distance for ' . $stake . '.');
                }

                $distance = $assignment->stakes[$stake];
                $maxLane  = min($range['max'], $lane->maxDistance());

                if ($distance < $range['min'] || $distance > $maxLane) {
                    return CommandResult::failure(
                        'Stake distance for ' . $stake . ' must be between ' . $range['min'] . ' and ' . $maxLane . '.',
                    );
                }

                $stakes[$stake] = $distance;
            }

            $stakeDistances = new StakeDistances($stakes);

            $collection->add(new TournamentTarget(
                round: $assignment->round,
                shootingLane: $lane,
                target: $target,
                distance: $stakeDistances->max(),
                stakes: $stakeDistances,
            ));
        }

        if ($collection->count() > $tournament->numberOfTargets()) {
            return CommandResult::failure('Number of assignments exceeds the configured target count.');
        }

        $tournament->replaceTargets($collection);
        $this->tournamentRepository->save($tournament);

        return CommandResult::success('Tournament assignments saved.');
    }

    /**
     * @param list<ShootingLane> $lanes
     *
     * @return array<string,ShootingLane>
     */
    private function buildLaneMap(array $lanes): array
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
    private function buildTargetMap(array $targets): array
    {
        $map = [];
        foreach ($targets as $target) {
            $map[$target->id()] = $target;
        }

        return $map;
    }
}
