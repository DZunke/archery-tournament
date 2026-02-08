<?php

declare(strict_types=1);

namespace App\Application\Command\Tournament;

final readonly class RegenerateTournament
{
    public function __construct(
        public string $tournamentId,
        public bool $randomizeStakesBetweenRounds,
        public bool $includeTrainingOnly = false,
    ) {
    }
}
