<?php

declare(strict_types=1);

namespace App\Application\Command\Tournament;

use App\Domain\ValueObject\Ruleset;
use DateTimeImmutable;

final readonly class CreateTournament
{
    public function __construct(
        public string $archeryGroundId,
        public string $name,
        public DateTimeImmutable $eventDate,
        public Ruleset $ruleset,
        public int $numberOfTargets,
        public bool $autoGenerate,
        public bool $randomizeStakesBetweenRounds,
        public bool $includeTrainingOnly = false,
    ) {
    }
}
