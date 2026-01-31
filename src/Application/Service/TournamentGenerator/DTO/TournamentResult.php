<?php

declare(strict_types=1);

namespace App\Application\Service\TournamentGenerator\DTO;

use App\Domain\Entity\ArcheryGround;
use App\Domain\Entity\ArcheryGround\ShootingLane;
use App\Domain\ValueObject\Ruleset;
use App\Domain\ValueObject\TargetType;

final class TournamentResult
{
    /** @var list<ShootingLane> */
    public array $availableLanes = [];
    public int $requiredRounds   = 1;
    /** @var array<value-of<TargetType>, array{type: TargetType, lanes: list<ShootingLane>}> */
    public array $selectedLanesPerTargetGroup = [];

    public function __construct(
        public readonly ArcheryGround $archeryGround,
        public readonly Ruleset $ruleset,
        public readonly int $numberOfTargets,
    ) {
    }
}
