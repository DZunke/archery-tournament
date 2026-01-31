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
use App\Domain\ValueObject\TargetType;

use function array_fill_keys;
use function array_map;
use function count;
use function intdiv;
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

        $collection              = new TournamentTargetCollection();
        $issues                  = [];
        $targetLaneAssignments   = [];
        $duplicateRows           = [];
        $laneRoundAssignments    = [];
        $duplicateLaneRows       = [];
        $laneTargetAssignments   = [];
        $duplicateLaneTargetRows = [];

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

            if ($lane instanceof ShootingLane && $target instanceof Target) {
                $existingTarget = $laneTargetAssignments[$lane->id()] ?? null;
                if ($existingTarget !== null && $existingTarget['targetId'] !== $target->id()) {
                    $duplicateKey = $lane->id() . ':' . $existingTarget['row'];
                    if (! isset($duplicateLaneTargetRows[$duplicateKey])) {
                        $issues[]                               = new TournamentValidationIssue(
                            rule: 'Lane Target Consistency',
                            message: 'Lane "' . $lane->name() . '" must keep the same target across rounds (row ' . $existingTarget['row'] . ' uses "' . $existingTarget['targetName'] . '").',
                            context: ['row' => $existingTarget['row']],
                        );
                        $duplicateLaneTargetRows[$duplicateKey] = true;
                    }

                    $issues[]    = new TournamentValidationIssue(
                        rule: 'Lane Target Consistency',
                        message: 'Lane "' . $lane->name() . '" must keep the same target across rounds (row ' . $existingTarget['row'] . ' uses "' . $existingTarget['targetName'] . '").',
                        context: ['row' => $rowNumber],
                    );
                    $rowHasIssue = true;
                } else {
                    $laneTargetAssignments[$lane->id()] = [
                        'targetId' => $target->id(),
                        'targetName' => $target->name(),
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

        if ($ruleset->supportsTargetGroupBalancing()) {
            $requiredTypes = $ruleset->requiredTargetTypes();
            $groupCount    = count($requiredTypes);
            if ($groupCount === 0) {
                $issues[] = new TournamentValidationIssue(
                    rule: 'Target Group Balance',
                    message: 'No target groups are configured for this ruleset.',
                );
            } else {
                $expectedTotal = $tournament->numberOfTargets();
                if ($expectedTotal % $groupCount !== 0) {
                    $issues[] = new TournamentValidationIssue(
                        rule: 'Target Group Balance',
                        message: 'Number of targets (' . $expectedTotal . ') must be divisible by the number of target groups (' . $groupCount . ').',
                        context: ['expected' => $expectedTotal, 'groups' => $groupCount],
                    );
                } elseif ($collection->count() === $expectedTotal) {
                    $expectedPerGroup = intdiv($expectedTotal, $groupCount);
                    $counts           = array_fill_keys(
                        array_map(static fn (TargetType $type): string => $type->value, $requiredTypes),
                        0,
                    );

                    foreach ($collection as $assignment) {
                        $type = $assignment->target()->type();
                        if (! isset($counts[$type->value])) {
                            continue;
                        }

                        $counts[$type->value]++;
                    }

                    foreach ($requiredTypes as $type) {
                        $actual = $counts[$type->value] ?? 0;
                        if ($actual === $expectedPerGroup) {
                            continue;
                        }

                        $issues[] = new TournamentValidationIssue(
                            rule: 'Target Group Balance',
                            message: 'Target group "' . $type->name . '" must be assigned ' . $expectedPerGroup . ' times but is assigned ' . $actual . '.',
                            context: ['group' => $type->value, 'expected' => $expectedPerGroup, 'actual' => $actual],
                        );
                    }
                }
            }
        }

        if ($collection->count() > $tournament->numberOfTargets()) {
            $expected = $tournament->numberOfTargets();
            $actual   = $collection->count();
            $overage  = $actual - $expected;
            $issues[] = new TournamentValidationIssue(
                rule: 'Target Count',
                message: 'Assignments exceed configured target count: expected ' . $expected . ', got ' . $actual . '. Remove ' . $overage . ' assignment(s).',
                context: [
                    'expected' => $expected,
                    'actual' => $actual,
                    'overage' => $overage,
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
