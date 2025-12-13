<?php

declare(strict_types=1);

namespace App\Application\Service\DTO;

use App\Domain\Entity\ArcheryGround;
use App\Domain\Entity\Tournament;
use App\Domain\Entity\TournamentTarget;
use App\Domain\ValueObject\StakeDistances;
use App\Domain\ValueObject\Ruleset;
use App\Domain\ValueObject\TargetType;

use function array_slice;
use function count;
use function floor;
use function min;
use function random_int;

final class GeneratedTournament
{
    /**
     * @param array<int, int> $lanesPerRound
     * @param list<array{lane: ArcheryGround\ShootingLane, type: TargetType}> $utilizedLanes
     */
    public function __construct(
        // Values used to generate the tournament
        public readonly ArcheryGround $archeryGround,
        public readonly Ruleset $ruleset,
        public readonly int $amountOfTargets,
        // Generated Values for Needed Rounds and Target Types per Round
        public int $neededRounds = 0, // How often do we walk through the same lane
        public array $lanesPerRound = [], // How many lanes should be used per round
        public array $utilizedLanes = [], // Which lanes will be utilized for which target type
    ) {
    }

    public function toTournament(string $name): Tournament
    {
        $tournament = Tournament::create(
            name: $name,
            ruleset: $this->ruleset,
            archeryGround: $this->archeryGround,
            numberOfTargets: $this->amountOfTargets,
        );

        return $tournament;
    }
}
