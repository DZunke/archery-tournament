<?php

declare(strict_types=1);

namespace App\Application\Command\ArcheryGround;

use App\Application\Command\CommandResult;
use App\Domain\Entity\ArcheryGround;
use App\Domain\Repository\ArcheryGroundRepository;
use App\Domain\Repository\TournamentRepository;

use function count;
use function implode;

final readonly class RemoveShootingLaneHandler
{
    public function __construct(
        private ArcheryGroundRepository $archeryGroundRepository,
        private TournamentRepository $tournamentRepository,
    ) {
    }

    public function __invoke(RemoveShootingLane $command): CommandResult
    {
        $archeryGround = $this->archeryGroundRepository->find($command->archeryGroundId);

        $laneName = 'Unknown';
        if ($archeryGround instanceof ArcheryGround) {
            foreach ($archeryGround->shootingLanes() as $lane) {
                if ($lane->id() === $command->laneId) {
                    $laneName = $lane->name();
                    break;
                }
            }
        }

        // Check if lane is used in any tournament
        $tournamentNames = $this->tournamentRepository->findTournamentNamesUsingLane($command->laneId);
        if (count($tournamentNames) > 0) {
            return CommandResult::failure(
                'Cannot remove lane "' . $laneName . '" because it is used in tournament(s): ' . implode(', ', $tournamentNames) . '.',
            );
        }

        $this->archeryGroundRepository->removeShootingLane($command->laneId);

        return CommandResult::success('The shooting lane "' . $laneName . '" was removed.');
    }
}
