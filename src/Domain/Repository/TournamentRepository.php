<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\Tournament;
use App\Domain\Entity\TournamentTargetCollection;

/**
 * Persistence boundary for tournaments and their assigned targets.
 */
interface TournamentRepository
{
    public function nextIdentity(): string;

    public function save(Tournament $tournament): void;

    public function find(string $id): Tournament|null;

    /** @return list<Tournament> */
    public function findAll(): array;

    /** @return list<Tournament> */
    public function findByArcheryGround(string $archeryGroundId): array;

    public function delete(string $id): void;

    public function replaceTargets(string $tournamentId, TournamentTargetCollection $targets): void;
}
