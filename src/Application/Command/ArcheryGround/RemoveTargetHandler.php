<?php

declare(strict_types=1);

namespace App\Application\Command\ArcheryGround;

use App\Application\Command\CommandResult;
use App\Application\Service\TargetImageStorage;
use App\Domain\Entity\ArcheryGround;
use App\Domain\Repository\ArcheryGroundRepository;

final readonly class RemoveTargetHandler
{
    public function __construct(
        private ArcheryGroundRepository $archeryGroundRepository,
        private TargetImageStorage $targetImageStorage,
    ) {
    }

    public function __invoke(RemoveTarget $command): CommandResult
    {
        $archeryGround = $this->archeryGroundRepository->find($command->archeryGroundId);
        if (! $archeryGround instanceof ArcheryGround) {
            return CommandResult::failure('Archery ground not found.');
        }

        $imagePath  = null;
        $targetName = 'Unknown';
        foreach ($archeryGround->targetStorage() as $target) {
            if ($target->id() === $command->targetId) {
                $imagePath  = $target->image();
                $targetName = $target->name();
                break;
            }
        }

        if ($imagePath !== null) {
            $this->targetImageStorage->remove($imagePath);
        }

        $this->archeryGroundRepository->removeTarget($command->targetId);

        return CommandResult::success('The target "' . $targetName . '" was removed.');
    }
}
