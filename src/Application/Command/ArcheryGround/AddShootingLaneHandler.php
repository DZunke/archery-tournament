<?php

declare(strict_types=1);

namespace App\Application\Command\ArcheryGround;

use App\Application\Command\CommandResult;
use App\Domain\Entity\ArcheryGround\ShootingLane;
use App\Domain\Repository\ArcheryGroundRepository;

final readonly class AddShootingLaneHandler
{
    public function __construct(private ArcheryGroundRepository $archeryGroundRepository)
    {
    }

    public function __invoke(AddShootingLane $command): CommandResult
    {
        $lane = new ShootingLane(
            id: $this->archeryGroundRepository->nextIdentity(),
            name: $command->name,
            maxDistance: $command->maxDistance,
            forTrainingOnly: $command->forTrainingOnly,
            notes: $command->notes,
        );

        $this->archeryGroundRepository->addShootingLane($command->archeryGroundId, $lane);

        return CommandResult::success('The shooting lane "' . $command->name . '" was added.');
    }
}
