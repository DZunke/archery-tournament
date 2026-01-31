<?php

declare(strict_types=1);

namespace App\Application\Query\Tournament;

use App\Domain\Entity\Tournament;
use App\Domain\Repository\TournamentRepository;
use DateTimeImmutable;

use function array_filter;
use function array_merge;
use function usort;

final readonly class ListTournamentsByArcheryGroundHandler
{
    public function __construct(private TournamentRepository $tournamentRepository)
    {
    }

    /** @return list<Tournament> */
    public function __invoke(ListTournamentsByArcheryGround $query): array
    {
        $tournaments = $this->tournamentRepository->findByArcheryGround($query->archeryGroundId);
        if ($tournaments === []) {
            return [];
        }

        $today = new DateTimeImmutable('today');

        $upcoming = array_filter(
            $tournaments,
            static fn (Tournament $tournament): bool => $tournament->eventDate() >= $today,
        );
        $past     = array_filter(
            $tournaments,
            static fn (Tournament $tournament): bool => $tournament->eventDate() < $today,
        );

        usort(
            $upcoming,
            static fn (Tournament $left, Tournament $right): int => $left->eventDate() <=> $right->eventDate(),
        );
        usort(
            $past,
            static fn (Tournament $left, Tournament $right): int => $right->eventDate() <=> $left->eventDate(),
        );

        return array_merge($upcoming, $past);
    }
}
