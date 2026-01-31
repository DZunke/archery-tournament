<?php

declare(strict_types=1);

namespace App\Application\Command\Tournament;

final readonly class DeleteTournament
{
    public function __construct(public string $tournamentId)
    {
    }
}
