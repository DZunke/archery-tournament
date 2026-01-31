<?php

declare(strict_types=1);

namespace App\Application\Service\TournamentValidation\Rule;

use App\Application\Service\TournamentValidation\TournamentValidationContext;
use App\Application\Service\TournamentValidation\TournamentValidationIssue;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

#[AsTaggedItem(priority: 150)]
final class TargetUniquenessRule implements TournamentValidationRule
{
    /** @return list<TournamentValidationIssue> */
    public function validate(TournamentValidationContext $context): array
    {
        $issues            = [];
        $targetAssignments = [];
        $duplicateRows     = [];

        foreach ($context->assignments as $assignment) {
            $target = $assignment->target;
            $lane   = $assignment->lane;
            if ($target === null) {
                continue;
            }

            if ($lane === null) {
                continue;
            }

            $existingLane = $targetAssignments[$target->id()] ?? null;
            if ($existingLane === null) {
                $targetAssignments[$target->id()] = [
                    'laneId' => $lane->id(),
                    'laneName' => $lane->name(),
                    'row' => $assignment->row,
                ];
                continue;
            }

            if ($existingLane['laneId'] === $lane->id()) {
                continue;
            }

            $message = 'Target "' . $target->name() . '" is assigned to multiple lanes (' . $existingLane['laneName'] . ', ' . $lane->name() . ').';

            if ($existingLane['row'] !== null) {
                $duplicateKey = $target->id() . ':' . $existingLane['row'];
                if (! isset($duplicateRows[$duplicateKey])) {
                    $issues[]                     = new TournamentValidationIssue(
                        rule: 'Target Uniqueness',
                        message: $message,
                        context: ['row' => $existingLane['row']],
                    );
                    $duplicateRows[$duplicateKey] = true;
                }
            }

            $contextRow = [];
            if ($assignment->row !== null) {
                $contextRow['row'] = $assignment->row;
            }

            $issues[] = new TournamentValidationIssue(
                rule: 'Target Uniqueness',
                message: $message,
                context: $contextRow,
            );
        }

        return $issues;
    }
}
