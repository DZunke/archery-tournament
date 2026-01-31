<?php

declare(strict_types=1);

namespace App\Application\Service\TournamentValidation;

final readonly class TournamentValidationResult
{
    /** @param list<TournamentValidationIssue> $issues */
    public function __construct(private array $issues)
    {
    }

    /** @return list<TournamentValidationIssue> */
    public function issues(): array
    {
        return $this->issues;
    }

    public function isValid(): bool
    {
        return $this->issues === [];
    }
}
