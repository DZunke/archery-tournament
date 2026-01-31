<?php

declare(strict_types=1);

namespace App\Application\Service\TournamentValidation;

final readonly class TournamentValidationIssue
{
    /** @param array<string, mixed> $context */
    public function __construct(
        public string $rule,
        public string $message,
        public array $context = [],
    ) {
    }
}
