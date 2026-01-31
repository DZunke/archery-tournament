<?php

declare(strict_types=1);

namespace App\Application\Command\ArcheryGround;

use App\Application\Command\CommandResult;
use App\Application\Service\TargetImageStorage;
use App\Domain\Entity\ArcheryGround;
use App\Domain\Repository\ArcheryGroundRepository;

final readonly class UpdateTargetImageHandler
{
    public function __construct(
        private ArcheryGroundRepository $archeryGroundRepository,
        private TargetImageStorage $targetImageStorage,
    ) {
    }

    public function __invoke(UpdateTargetImage $command): CommandResult
    {
        $archeryGround = $this->archeryGroundRepository->find($command->archeryGroundId);
        if (! $archeryGround instanceof ArcheryGround) {
            return CommandResult::failure('Archery ground not found.');
        }

        $existingImage = null;
        foreach ($archeryGround->targetStorage() as $target) {
            if ($target->id() === $command->targetId) {
                $existingImage = $target->image();
                break;
            }
        }

        if ($existingImage === null) {
            return CommandResult::failure('Target not found.');
        }

        $newImage = $this->targetImageStorage->store($command->image, $command->targetId);
        $this->archeryGroundRepository->updateTargetImage($command->targetId, $newImage);

        if ($existingImage !== $newImage) {
            $this->targetImageStorage->remove($existingImage);
        }

        return CommandResult::success('Target image updated.');
    }
}
