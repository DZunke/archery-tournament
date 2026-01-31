<?php

declare(strict_types=1);

namespace App\Application\Query\Tournament;

use App\Domain\Entity\Tournament;
use App\Domain\Repository\TournamentRepository;

final readonly class ListTournamentsHandler
{
    public function __construct(private TournamentRepository $tournamentRepository)
    {
    }

    /** @return list<Tournament> */
    public function __invoke(ListTournaments $query): array
    {
        unset($query);

        return $this->tournamentRepository->findAll();
    }
}
