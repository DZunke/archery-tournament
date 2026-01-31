<?php

declare(strict_types=1);

namespace App\Application\Command\Tournament;

final readonly class TournamentTargetAssignment
{
    /** @param array<string,int> $stakes */
    public function __construct(
        public int $round,
        public string $shootingLaneId,
        public string $targetId,
        public array $stakes,
    ) {
    }
}
