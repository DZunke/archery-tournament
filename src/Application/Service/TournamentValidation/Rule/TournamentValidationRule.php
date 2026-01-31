<?php

declare(strict_types=1);

namespace App\Application\Service\TournamentValidation\Rule;

use App\Application\Service\TournamentValidation\TournamentValidationContext;
use App\Application\Service\TournamentValidation\TournamentValidationIssue;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag]
interface TournamentValidationRule
{
    /** @return list<TournamentValidationIssue> */
    public function validate(TournamentValidationContext $context): array;
}
