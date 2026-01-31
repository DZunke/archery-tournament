<?php

declare(strict_types=1);

namespace App\Application\Command\ArcheryGround;

use App\Application\Command\CommandResult;
use App\Domain\Entity\ArcheryGround;
use App\Domain\Repository\ArcheryGroundRepository;

use function trim;

final readonly class RenameArcheryGroundHandler
{
    public function __construct(private ArcheryGroundRepository $archeryGroundRepository)
    {
    }

    public function __invoke(RenameArcheryGround $command): CommandResult
    {
        $name = trim($command->name);
        if ($name === '') {
            return CommandResult::failure('Name cannot be empty.');
        }

        $archeryGround = $this->archeryGroundRepository->find($command->id);
        if (! $archeryGround instanceof ArcheryGround) {
            return CommandResult::failure('Archery ground not found.');
        }

        $updated = new ArcheryGround(
            id: $archeryGround->id(),
            name: $name,
            targetStorage: $archeryGround->targetStorage(),
            shootingLanes: $archeryGround->shootingLanes(),
        );

        $this->archeryGroundRepository->save($updated);

        return CommandResult::success('Archery ground renamed.');
    }
}
