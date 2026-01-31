<?php

declare(strict_types=1);

namespace App\Application\Query\Tournament;

use App\Domain\Entity\Tournament;
use App\Domain\Repository\TournamentRepository;

final readonly class GetTournamentHandler
{
    public function __construct(private TournamentRepository $tournamentRepository)
    {
    }

    public function __invoke(GetTournament $query): Tournament|null
    {
        return $this->tournamentRepository->find($query->id);
    }
}
