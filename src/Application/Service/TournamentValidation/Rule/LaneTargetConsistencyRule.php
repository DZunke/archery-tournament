<?php

declare(strict_types=1);

namespace App\Application\Service\TournamentValidation\Rule;

use App\Application\Service\TournamentValidation\TournamentValidationContext;
use App\Application\Service\TournamentValidation\TournamentValidationIssue;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

#[AsTaggedItem(priority: 145)]
final class LaneTargetConsistencyRule implements TournamentValidationRule
{
    /** @return list<TournamentValidationIssue> */
    public function validate(TournamentValidationContext $context): array
    {
        $issues                = [];
        $laneTargetAssignments = [];
        $duplicateLaneRows     = [];

        foreach ($context->assignments as $assignment) {
            $lane   = $assignment->lane;
            $target = $assignment->target;
            if ($lane === null) {
                continue;
            }

            if ($target === null) {
                continue;
            }

            $existingTarget = $laneTargetAssignments[$lane->id()] ?? null;
            if ($existingTarget === null) {
                $laneTargetAssignments[$lane->id()] = [
                    'targetId' => $target->id(),
                    'targetName' => $target->name(),
                    'row' => $assignment->row,
                    'laneName' => $lane->name(),
                ];
                continue;
            }

            if ($existingTarget['targetId'] === $target->id()) {
                continue;
            }

            $existingRow = $existingTarget['row'];
            $detail      = $existingRow !== null
                ? 'row ' . $existingRow . ' uses "' . $existingTarget['targetName'] . '"'
                : 'another round uses "' . $existingTarget['targetName'] . '"';
            $message     = 'Lane "' . $lane->name() . '" must keep the same target across rounds (' . $detail . ').';

            if ($existingRow !== null) {
                $duplicateKey = $lane->id() . ':' . $existingRow;
                if (! isset($duplicateLaneRows[$duplicateKey])) {
                    $issues[]                         = new TournamentValidationIssue(
                        rule: 'Lane Target Consistency',
                        message: $message,
                        context: ['row' => $existingRow],
                    );
                    $duplicateLaneRows[$duplicateKey] = true;
                }
            }

            $contextRow = [];
            if ($assignment->row !== null) {
                $contextRow['row'] = $assignment->row;
            }

            $issues[] = new TournamentValidationIssue(
                rule: 'Lane Target Consistency',
                message: $message,
                context: $contextRow,
            );
        }

        return $issues;
    }
}
