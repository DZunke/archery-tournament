<?php

declare(strict_types=1);

namespace App\Application\Command\ArcheryGround;

use App\Application\Command\CommandResult;
use App\Domain\Entity\ArcheryGround;
use App\Domain\Repository\ArcheryGroundRepository;

final readonly class UpdateShootingLaneHandler
{
    public function __construct(private ArcheryGroundRepository $archeryGroundRepository)
    {
    }

    public function __invoke(UpdateShootingLane $command): CommandResult
    {
        $archeryGround = $this->archeryGroundRepository->find($command->archeryGroundId);
        if (! $archeryGround instanceof ArcheryGround) {
            return CommandResult::failure('Archery ground not found.');
        }

        $laneExists = false;
        foreach ($archeryGround->shootingLanes() as $lane) {
            if ($lane->id() === $command->laneId) {
                $laneExists = true;
                break;
            }
        }

        if (! $laneExists) {
            return CommandResult::failure('Lane not found.');
        }

        $this->archeryGroundRepository->updateShootingLane(
            archeryGroundId: $command->archeryGroundId,
            laneId: $command->laneId,
            name: $command->name,
            maxDistance: $command->maxDistance,
        );

        return CommandResult::success('Lane updated.');
    }
}
