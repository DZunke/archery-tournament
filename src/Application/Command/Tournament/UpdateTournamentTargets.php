<?php

declare(strict_types=1);

namespace App\Application\Command\Tournament;

final readonly class UpdateTournamentTargets
{
    /** @param list<TournamentTargetAssignment> $assignments */
    public function __construct(
        public string $tournamentId,
        public array $assignments,
    ) {
    }
}
