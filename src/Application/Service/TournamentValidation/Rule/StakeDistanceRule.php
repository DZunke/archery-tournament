<?php

declare(strict_types=1);

namespace App\Application\Service\TournamentValidation\Rule;

use App\Application\Service\TournamentValidation\TournamentValidationIssue;
use App\Domain\Entity\Tournament;
use App\Domain\Entity\TournamentTarget;
use App\Domain\ValueObject\Ruleset;
use App\Domain\ValueObject\TargetType;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

use function array_merge;
use function in_array;
use function min;

#[AsTaggedItem(priority: 200)]
final class StakeDistanceRule implements TournamentValidationRule
{
    /** @return list<TournamentValidationIssue> */
    public function validate(Tournament $tournament): array
    {
        $issues       = [];
        $ruleset      = $tournament->ruleset();
        $allowedTypes = $ruleset->allowedTargetTypes();

        foreach ($tournament->targets() as $assignment) {
            $issues = array_merge($issues, $this->validateAssignment($assignment, $allowedTypes, $ruleset));
        }

        return $issues;
    }

    /**
     * @param list<TargetType> $allowedTypes
     *
     * @return list<TournamentValidationIssue>
     */
    private function validateAssignment(TournamentTarget $assignment, array $allowedTypes, Ruleset $ruleset): array
    {
        $issues     = [];
        $target     = $assignment->target();
        $lane       = $assignment->shootingLane();
        $targetType = $target->type();

        if (! in_array($targetType, $allowedTypes, true)) {
            $issues[] = new TournamentValidationIssue(
                rule: 'Target Type',
                message: 'Target "' . $target->name() . '" uses a type not allowed by the ruleset.',
            );

            return $issues;
        }

        $ranges = $ruleset->stakeDistanceRanges($targetType);
        $stakes = $assignment->stakes()->all();

        foreach ($ranges as $stake => $range) {
            if (! isset($stakes[$stake])) {
                $issues[] = new TournamentValidationIssue(
                    rule: 'Stake Distance',
                    message: 'Stake "' . $stake . '" is missing for target "' . $target->name() . '" (round ' . $assignment->round() . ').',
                );
                continue;
            }

            $distance   = $stakes[$stake];
            $maxAllowed = min($range['max'], $lane->maxDistance());

            if ($distance >= $range['min'] && $distance <= $maxAllowed) {
                continue;
            }

            $issues[] = new TournamentValidationIssue(
                rule: 'Stake Distance',
                message: 'Stake "' . $stake . '" for target "' . $target->name() . '" (round ' . $assignment->round() . ') must be between ' . $range['min'] . 'm and ' . $maxAllowed . 'm.',
                context: [
                    'stake' => $stake,
                    'min' => $range['min'],
                    'max' => $maxAllowed,
                    'actual' => $distance,
                ],
            );
        }

        foreach ($stakes as $stake => $distance) {
            if (isset($ranges[$stake])) {
                continue;
            }

            $issues[] = new TournamentValidationIssue(
                rule: 'Stake Distance',
                message: 'Stake "' . $stake . '" is not defined in the ruleset for target "' . $target->name() . '".',
                context: ['stake' => $stake, 'actual' => $distance],
            );
        }

        return $issues;
    }
}
