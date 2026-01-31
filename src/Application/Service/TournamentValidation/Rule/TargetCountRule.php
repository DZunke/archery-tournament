<?php

declare(strict_types=1);

namespace App\Application\Service\TournamentValidation\Rule;

use App\Application\Service\TournamentValidation\TournamentValidationIssue;
use App\Domain\Entity\Tournament;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

#[AsTaggedItem(priority: 100)]
final class TargetCountRule implements TournamentValidationRule
{
    /** @return list<TournamentValidationIssue> */
    public function validate(Tournament $tournament): array
    {
        $issues   = [];
        $expected = $tournament->numberOfTargets();
        $actual   = $tournament->targets()->count();

        if ($actual < $expected) {
            $issues[] = new TournamentValidationIssue(
                rule: 'Target Count',
                message: 'Tournament has ' . $actual . ' targets assigned but expects ' . $expected . '.',
                context: ['expected' => $expected, 'actual' => $actual],
            );
        }

        if ($actual > $expected) {
            $issues[] = new TournamentValidationIssue(
                rule: 'Target Count',
                message: 'Tournament has ' . $actual . ' targets assigned but only ' . $expected . ' are allowed.',
                context: ['expected' => $expected, 'actual' => $actual],
            );
        }

        return $issues;
    }
}
