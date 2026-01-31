<?php

declare(strict_types=1);

namespace App\Application\Service\TournamentValidation\Rule;

use App\Application\Service\TournamentValidation\TournamentValidationContext;
use App\Application\Service\TournamentValidation\TournamentValidationIssue;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

#[AsTaggedItem(priority: 250)]
final class AssignmentReferenceRule implements TournamentValidationRule
{
    /** @return list<TournamentValidationIssue> */
    public function validate(TournamentValidationContext $context): array
    {
        $issues = [];

        foreach ($context->assignments as $assignment) {
            $contextRow = [];
            if ($assignment->row !== null) {
                $contextRow['row'] = $assignment->row;
            }

            if ($assignment->round <= 0) {
                $issues[] = new TournamentValidationIssue(
                    rule: 'Round Number',
                    message: 'Round must be greater than zero.',
                    context: $contextRow,
                );
            }

            if ($assignment->lane === null) {
                $issues[] = new TournamentValidationIssue(
                    rule: 'Shooting Lane',
                    message: 'Invalid shooting lane selected.',
                    context: $contextRow,
                );
            }

            if ($assignment->target !== null) {
                continue;
            }

            $issues[] = new TournamentValidationIssue(
                rule: 'Target',
                message: 'Invalid target selected.',
                context: $contextRow,
            );
        }

        return $issues;
    }
}
