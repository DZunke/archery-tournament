<?php

declare(strict_types=1);

namespace App\Application\Command\ArcheryGround;

use App\Application\Command\CommandResult;
use App\Domain\Entity\ArcheryGround\ShootingLane;
use App\Domain\Repository\ArcheryGroundRepository;

use function is_numeric;
use function trim;

final readonly class AddShootingLaneHandler
{
    public function __construct(private ArcheryGroundRepository $archeryGroundRepository)
    {
    }

    public function __invoke(AddShootingLane $command): CommandResult
    {
        $name = trim($command->name);
        if ($name === '') {
            return CommandResult::failure('Lane name is required.');
        }

        if ($command->maxDistance === '' || ! is_numeric($command->maxDistance)) {
            return CommandResult::failure('Max distance must be a number.');
        }

        $maxDistance = (float) $command->maxDistance;
        if ($maxDistance <= 0) {
            return CommandResult::failure('Max distance must be greater than zero.');
        }

        $lane = new ShootingLane(
            id: $this->archeryGroundRepository->nextIdentity(),
            name: $name,
            maxDistance: $maxDistance,
        );

        $this->archeryGroundRepository->addShootingLane($command->archeryGroundId, $lane);

        return CommandResult::success('Lane added.');
    }
}
