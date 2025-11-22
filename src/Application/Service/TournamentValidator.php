<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Domain\Entity\Tournament;
use App\Domain\Entity\TournamentTarget;
use App\Domain\ValueObject\TargetType;

use function count;
use function sprintf;

final class TournamentValidator
{
    /** @return list<string> */
    public function validate(Tournament $tournament): array
    {
        $targets = $tournament->targets();
        if (count($targets) === 0) {
            return ['Tournament must contain at least one target assignment.'];
        }

        $ruleset       = $tournament->ruleset();
        $requiredTypes = $ruleset->requiredTargetTypes();

        $typesUsed   = [];
        $roundsTypes = [];
        $errors      = [];

        foreach ($targets as $assignment) {
            $this->collectUsage($assignment, $typesUsed, $roundsTypes);
            $errors = [
                ...$errors,
                ...$this->validateStakes($assignment, $ruleset->stakeDistanceRanges($assignment->target()->type())),
            ];
        }

        return [
            ...$errors,
            ...$this->validateRequiredTypesUsed($requiredTypes, $typesUsed),
            ...$this->validateRoundCoverage($requiredTypes, $roundsTypes),
        ];
    }

    /**
     * @param array<string,bool>            $typesUsed
     * @param array<int,array<string,bool>> $roundsTypes
     */
    private function collectUsage(TournamentTarget $assignment, array &$typesUsed, array &$roundsTypes): void
    {
        $typeKey             = $assignment->target()->type()->value;
        $typesUsed[$typeKey] = true;

        $round                         = $assignment->round();
        $roundsTypes[$round]         ??= [];
        $roundsTypes[$round][$typeKey] = true;
    }

    /**
     * @param array<string,array{min:float,max:float}> $stakeRanges
     *
     * @return list<string>
     */
    private function validateStakes(TournamentTarget $assignment, array $stakeRanges): array
    {
        $errors = [];
        $type   = $assignment->target()->type()->value;
        $round  = $assignment->round();
        $lane   = $assignment->shootingLane();

        foreach ($assignment->stakes()->all() as $stake => $distance) {
            if (! isset($stakeRanges[$stake])) {
                $errors[] = sprintf(
                    'Stake "%s" is not allowed for target type %s (round %d, lane %s).',
                    $stake,
                    $type,
                    $round,
                    $lane->name(),
                );
                continue;
            }

            $range = $stakeRanges[$stake];
            if ($distance < $range['min'] || $distance > $range['max']) {
                $errors[] = sprintf(
                    'Stake "%s" distance %d is outside allowed range [%s, %s] for type %s (round %d, lane %s).',
                    $stake,
                    $distance,
                    $range['min'],
                    $range['max'],
                    $type,
                    $round,
                    $lane->name(),
                );
            }

            if ($distance <= $lane->maxDistance()) {
                continue;
            }

            $errors[] = sprintf(
                'Stake "%s" distance %d exceeds lane "%s" max distance %s (round %d).',
                $stake,
                $distance,
                $lane->name(),
                $lane->maxDistance(),
                $round,
            );
        }

        return $errors;
    }

    /**
     * @param list<TargetType>   $requiredTypes
     * @param array<string,bool> $typesUsed
     *
     * @return list<string>
     */
    private function validateRequiredTypesUsed(array $requiredTypes, array $typesUsed): array
    {
        $errors = [];
        foreach ($requiredTypes as $requiredType) {
            if (isset($typesUsed[$requiredType->value])) {
                continue;
            }

            $errors[] = sprintf('Required target type %s was not used in any assignment.', $requiredType->value);
        }

        return $errors;
    }

    /**
     * @param list<TargetType>              $requiredTypes
     * @param array<int,array<string,bool>> $roundsTypes
     *
     * @return list<string>
     */
    private function validateRoundCoverage(array $requiredTypes, array $roundsTypes): array
    {
        $errors = [];
        foreach ($roundsTypes as $round => $typesInRound) {
            foreach ($requiredTypes as $requiredType) {
                if (isset($typesInRound[$requiredType->value])) {
                    continue;
                }

                $errors[] = sprintf('Round %d is missing required target type %s.', $round, $requiredType->value);
            }
        }

        return $errors;
    }
}
