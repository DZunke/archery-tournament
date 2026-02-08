<?php

declare(strict_types=1);

namespace App\Presentation\Input\Tournament;

use App\Application\Command\Tournament\RegenerateTournament;
use Symfony\Component\HttpFoundation\Request;

final readonly class RegenerateTournamentInput
{
    public function __construct(
        public bool $randomizeStakesBetweenRounds,
        public bool $includeTrainingOnly,
    ) {
    }

    public static function fromRequest(Request $request): self
    {
        return new self(
            $request->request->getBoolean('randomize_stakes_between_rounds'),
            $request->request->getBoolean('include_training_only'),
        );
    }

    public function toCommand(string $tournamentId): RegenerateTournament
    {
        return new RegenerateTournament(
            $tournamentId,
            $this->randomizeStakesBetweenRounds,
            $this->includeTrainingOnly,
        );
    }
}
