<?php

declare(strict_types=1);

namespace App\Application\Command\Tournament;

use App\Application\Command\CommandResult;
use App\Domain\Entity\Tournament;
use App\Domain\Repository\TournamentRepository;

final readonly class DeleteTournamentHandler
{
    public function __construct(private TournamentRepository $tournamentRepository)
    {
    }

    public function __invoke(DeleteTournament $command): CommandResult
    {
        $tournament     = $this->tournamentRepository->find($command->tournamentId);
        $tournamentName = $tournament instanceof Tournament ? $tournament->name() : 'Unknown';

        $this->tournamentRepository->delete($command->tournamentId);

        return CommandResult::success('The tournament "' . $tournamentName . '" was deleted.');
    }
}
