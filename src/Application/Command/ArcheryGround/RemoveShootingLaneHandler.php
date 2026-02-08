<?php

declare(strict_types=1);

namespace App\Application\Command\ArcheryGround;

use App\Application\Command\CommandResult;
use App\Domain\Entity\ArcheryGround;
use App\Domain\Repository\ArcheryGroundRepository;

final readonly class RemoveShootingLaneHandler
{
    public function __construct(private ArcheryGroundRepository $archeryGroundRepository)
    {
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

        $this->archeryGroundRepository->removeShootingLane($command->laneId);

        return CommandResult::success('The shooting lane "' . $laneName . '" was removed.');
    }
}
