<?php

declare(strict_types=1);

namespace App\Application\Command\ArcheryGround;

use App\Application\Command\CommandResult;
use App\Domain\Repository\ArcheryGroundRepository;

final readonly class RemoveShootingLaneHandler
{
    public function __construct(private ArcheryGroundRepository $archeryGroundRepository)
    {
    }

    public function __invoke(RemoveShootingLane $command): CommandResult
    {
        $this->archeryGroundRepository->removeShootingLane($command->laneId);

        return CommandResult::success('Lane removed.');
    }
}
