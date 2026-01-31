<?php

declare(strict_types=1);

namespace App\Application\Query\Tournament;

final readonly class GetTournament
{
    public function __construct(public string $id)
    {
    }
}
