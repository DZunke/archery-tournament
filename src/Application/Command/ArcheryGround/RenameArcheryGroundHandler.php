<?php

declare(strict_types=1);

namespace App\Application\Command\ArcheryGround;

use App\Application\Command\CommandResult;
use App\Domain\Entity\ArcheryGround;
use App\Domain\Repository\ArcheryGroundRepository;

final readonly class RenameArcheryGroundHandler
{
    public function __construct(private ArcheryGroundRepository $archeryGroundRepository)
    {
    }

    public function __invoke(RenameArcheryGround $command): CommandResult
    {
        $archeryGround = $this->archeryGroundRepository->find($command->id);
        if (! $archeryGround instanceof ArcheryGround) {
            return CommandResult::failure('Archery ground not found.');
        }

        $updated = new ArcheryGround(
            id: $archeryGround->id(),
            name: $command->name,
            targetStorage: $archeryGround->targetStorage(),
            shootingLanes: $archeryGround->shootingLanes(),
        );

        $this->archeryGroundRepository->save($updated);

        return CommandResult::success('The archery ground was renamed to "' . $command->name . '".');
    }
}
