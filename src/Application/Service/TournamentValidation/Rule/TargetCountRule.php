<?php

declare(strict_types=1);

namespace App\Application\Service\TournamentValidation\Rule;

use App\Application\Service\TournamentValidation\TournamentValidationContext;
use App\Application\Service\TournamentValidation\TournamentValidationIssue;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

use function array_filter;
use function count;

#[AsTaggedItem(priority: 100)]
final class TargetCountRule implements TournamentValidationRule
{
    /** @return list<TournamentValidationIssue> */
    public function validate(TournamentValidationContext $context): array
    {
        $issues   = [];
        $expected = $context->expectedTargetCount;
        $actual   = count(array_filter(
            $context->assignments,
            static fn ($assignment): bool => $assignment->round > 0
                && $assignment->lane !== null
                && $assignment->target !== null,
        ));

        if ($actual < $expected) {
            $missing  = $expected - $actual;
            $issues[] = new TournamentValidationIssue(
                rule: 'Target Count',
                message: 'Assignments are below the configured target count: expected ' . $expected . ', got ' . $actual . '. Add ' . $missing . ' assignment(s).',
                context: ['expected' => $expected, 'actual' => $actual, 'missing' => $missing],
            );
        }

        if ($actual > $expected) {
            $overage  = $actual - $expected;
            $issues[] = new TournamentValidationIssue(
                rule: 'Target Count',
                message: 'Assignments exceed the configured target count: expected ' . $expected . ', got ' . $actual . '. Remove ' . $overage . ' assignment(s).',
                context: ['expected' => $expected, 'actual' => $actual, 'overage' => $overage],
            );
        }

        return $issues;
    }
}
