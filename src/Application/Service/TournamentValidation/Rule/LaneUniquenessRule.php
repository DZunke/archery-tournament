<?php

declare(strict_types=1);

namespace App\Application\Service\TournamentValidation\Rule;

use App\Application\Service\TournamentValidation\TournamentValidationContext;
use App\Application\Service\TournamentValidation\TournamentValidationIssue;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

#[AsTaggedItem(priority: 140)]
final class LaneUniquenessRule implements TournamentValidationRule
{
    /** @return list<TournamentValidationIssue> */
    public function validate(TournamentValidationContext $context): array
    {
        $issues        = [];
        $assignments   = [];
        $duplicateRows = [];

        foreach ($context->assignments as $assignment) {
            $lane  = $assignment->lane;
            $round = $assignment->round;
            if ($lane === null) {
                continue;
            }

            if ($round <= 0) {
                continue;
            }

            $existingLane = $assignments[$round][$lane->id()] ?? null;
            if ($existingLane === null) {
                $assignments[$round][$lane->id()] = [
                    'laneName' => $lane->name(),
                    'row' => $assignment->row,
                ];
                continue;
            }

            $message     = 'Lane "' . $lane->name() . '" is already used in round ' . $round;
            $existingRow = $existingLane['row'];

            if ($existingRow !== null) {
                $duplicateKey = $round . ':' . $lane->id() . ':' . $existingRow;
                if (! isset($duplicateRows[$duplicateKey])) {
                    $issues[]                     = new TournamentValidationIssue(
                        rule: 'Lane Uniqueness',
                        message: $message . ' (row ' . $existingRow . ').',
                        context: ['row' => $existingRow, 'round' => $round],
                    );
                    $duplicateRows[$duplicateKey] = true;
                }
            }

            $contextRow = ['round' => $round];
            if ($assignment->row !== null) {
                $contextRow['row'] = $assignment->row;
            }

            $issues[] = new TournamentValidationIssue(
                rule: 'Lane Uniqueness',
                message: $existingRow !== null ? $message . ' (row ' . $existingRow . ').' : $message . '.',
                context: $contextRow,
            );
        }

        return $issues;
    }
}
