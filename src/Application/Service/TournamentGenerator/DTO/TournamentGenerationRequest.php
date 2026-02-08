<?php

declare(strict_types=1);

namespace App\Application\Service\TournamentGenerator\DTO;

use App\Domain\Entity\ArcheryGround;
use App\Domain\ValueObject\Ruleset;

final readonly class TournamentGenerationRequest
{
    public function __construct(
        public ArcheryGround $archeryGround,
        public Ruleset $ruleset,
        public int $amountOfTargets,
        public bool $randomizeStakesBetweenRounds = false,
        public bool $includeTrainingOnly = false,
    ) {
    }
}
