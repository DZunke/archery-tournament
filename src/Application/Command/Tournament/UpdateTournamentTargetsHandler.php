<?php

declare(strict_types=1);

namespace App\Application\Command\Tournament;

use App\Application\Command\CommandResult;
use App\Application\Service\TournamentValidation\TournamentValidationIssue;
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

        $collection            = new TournamentTargetCollection();
        $issues                = [];
        $targetLaneAssignments = [];
        $duplicateRows         = [];
        $laneRoundAssignments  = [];
        $duplicateLaneRows     = [];

        foreach ($command->assignments as $assignment) {
            $rowNumber   = $assignment->rowIndex + 1;
            $rowHasIssue = false;

            if ($assignment->round <= 0) {
                $issues[]    = new TournamentValidationIssue(
                    rule: 'Round Number',
                    message: 'Round must be greater than zero.',
                    context: ['row' => $rowNumber],
                );
                $rowHasIssue = true;
            }

            $lane   = $laneMap[$assignment->shootingLaneId] ?? null;
            $target = $targetMap[$assignment->targetId] ?? null;

            if (! $lane instanceof ShootingLane) {
                $issues[]    = new TournamentValidationIssue(
                    rule: 'Shooting Lane',
                    message: 'Invalid shooting lane selected.',
                    context: ['row' => $rowNumber],
                );
                $rowHasIssue = true;
            }

            if (! $target instanceof Target) {
                $issues[]    = new TournamentValidationIssue(
                    rule: 'Target',
                    message: 'Invalid target selected.',
                    context: ['row' => $rowNumber],
                );
                $rowHasIssue = true;
            }

            if ($lane instanceof ShootingLane && $target instanceof Target) {
                $existingLane = $targetLaneAssignments[$target->id()] ?? null;
                if ($existingLane !== null && $existingLane['laneId'] !== $lane->id()) {
                    $duplicateKey = $target->id() . ':' . $existingLane['row'];
                    if (! isset($duplicateRows[$duplicateKey])) {
                        $issues[]                     = new TournamentValidationIssue(
                            rule: 'Target Uniqueness',
                            message: 'Target "' . $target->name() . '" is already assigned to a different lane (row ' . $existingLane['row'] . ').',
                            context: ['row' => $existingLane['row']],
                        );
                        $duplicateRows[$duplicateKey] = true;
                    }

                    $issues[]    = new TournamentValidationIssue(
                        rule: 'Target Uniqueness',
                        message: 'Target "' . $target->name() . '" is already assigned to a different lane (row ' . $existingLane['row'] . ').',
                        context: ['row' => $rowNumber],
                    );
                    $rowHasIssue = true;
                } else {
                    $targetLaneAssignments[$target->id()] = [
                        'laneId' => $lane->id(),
                        'row' => $rowNumber,
                    ];
                }
            }

            if ($lane instanceof ShootingLane && $assignment->round > 0) {
                $existingLane = $laneRoundAssignments[$assignment->round][$lane->id()] ?? null;
                if ($existingLane !== null && $existingLane['row'] !== $rowNumber) {
                    $duplicateKey = $assignment->round . ':' . $lane->id() . ':' . $existingLane['row'];
                    if (! isset($duplicateLaneRows[$duplicateKey])) {
                        $issues[]                         = new TournamentValidationIssue(
                            rule: 'Lane Uniqueness',
                            message: 'Lane "' . $lane->name() . '" is already used in round ' . $assignment->round . ' (row ' . $existingLane['row'] . ').',
                            context: ['row' => $existingLane['row'], 'round' => $assignment->round],
                        );
                        $duplicateLaneRows[$duplicateKey] = true;
                    }

                    $issues[]    = new TournamentValidationIssue(
                        rule: 'Lane Uniqueness',
                        message: 'Lane "' . $lane->name() . '" is already used in round ' . $assignment->round . ' (row ' . $existingLane['row'] . ').',
                        context: ['row' => $rowNumber, 'round' => $assignment->round],
                    );
                    $rowHasIssue = true;
                } else {
                    $laneRoundAssignments[$assignment->round][$lane->id()] = ['row' => $rowNumber];
                }
            }

            if ($rowHasIssue) {
                continue;
            }

            if (! $lane instanceof ShootingLane) {
                continue;
            }

            if (! $target instanceof Target) {
                continue;
            }

            $targetType = $target->type();
            $ranges     = $ruleset->stakeDistanceRanges($targetType);
            $stakes     = [];

            foreach ($ranges as $stake => $range) {
                if (! isset($assignment->stakes[$stake])) {
                    $issues[]    = new TournamentValidationIssue(
                        rule: 'Stake Distance',
                        message: 'Missing stake distance for "' . $stake . '".',
                        context: ['row' => $rowNumber, 'stake' => $stake],
                    );
                    $rowHasIssue = true;
                    continue;
                }

                $distance = $assignment->stakes[$stake];
                $maxLane  = min($range['max'], $lane->maxDistance());

                if ($distance < $range['min'] || $distance > $maxLane) {
                    $issues[]    = new TournamentValidationIssue(
                        rule: 'Stake Distance',
                        message: 'Stake "' . $stake . '" must be between ' . $range['min'] . 'm and ' . $maxLane . 'm.',
                        context: [
                            'row' => $rowNumber,
                            'stake' => $stake,
                            'min' => $range['min'],
                            'max' => $maxLane,
                            'actual' => $distance,
                        ],
                    );
                    $rowHasIssue = true;
                    continue;
                }

                $stakes[$stake] = $distance;
            }

            if ($rowHasIssue) {
                continue;
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
            $issues[] = new TournamentValidationIssue(
                rule: 'Target Count',
                message: 'Number of assignments exceeds the configured target count.',
                context: [
                    'expected' => $tournament->numberOfTargets(),
                    'actual' => $collection->count(),
                ],
            );
        }

        if ($issues !== []) {
            return CommandResult::failure('Tournament assignments failed validation.', ['issues' => $issues]);
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
