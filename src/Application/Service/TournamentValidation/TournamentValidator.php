<?php

declare(strict_types=1);

namespace App\Application\Service\TournamentValidation;

use App\Application\Service\TournamentValidation\Rule\TournamentValidationRule;
use App\Domain\Entity\Tournament;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

final readonly class TournamentValidator
{
    /** @param iterable<TournamentValidationRule> $rules */
    public function __construct(
        #[AutowireIterator(tag: TournamentValidationRule::class)] private iterable $rules,
    ) {
    }

    public function validate(Tournament $tournament): TournamentValidationResult
    {
        $issues = [];

        foreach ($this->rules as $rule) {
            foreach ($rule->validate($tournament) as $issue) {
                $issues[] = $issue;
            }
        }

        return new TournamentValidationResult($issues);
    }
}
