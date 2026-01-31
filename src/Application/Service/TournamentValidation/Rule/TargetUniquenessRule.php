<?php

declare(strict_types=1);

namespace App\Application\Service\TournamentValidation\Rule;

use App\Application\Service\TournamentValidation\TournamentValidationIssue;
use App\Domain\Entity\Tournament;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

#[AsTaggedItem(priority: 150)]
final class TargetUniquenessRule implements TournamentValidationRule
{
    /** @return list<TournamentValidationIssue> */
    public function validate(Tournament $tournament): array
    {
        $issues            = [];
        $targetAssignments = [];

        foreach ($tournament->targets() as $assignment) {
            $target = $assignment->target();
            $lane   = $assignment->shootingLane();

            $existingLane = $targetAssignments[$target->id()] ?? null;
            if ($existingLane === null) {
                $targetAssignments[$target->id()] = [
                    'laneId' => $lane->id(),
                    'laneName' => $lane->name(),
                ];
                continue;
            }

            if ($existingLane['laneId'] === $lane->id()) {
                continue;
            }

            $issues[] = new TournamentValidationIssue(
                rule: 'Target Uniqueness',
                message: 'Target "' . $target->name() . '" is assigned to multiple lanes (' . $existingLane['laneName'] . ', ' . $lane->name() . ').',
            );
        }

        return $issues;
    }
}
