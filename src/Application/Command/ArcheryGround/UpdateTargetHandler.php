<?php

declare(strict_types=1);

namespace App\Application\Command\ArcheryGround;

use App\Application\Command\CommandResult;
use App\Application\Service\TargetImageStorage;
use App\Domain\Entity\ArcheryGround;
use App\Domain\Repository\ArcheryGroundRepository;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final readonly class UpdateTargetHandler
{
    public function __construct(
        private ArcheryGroundRepository $archeryGroundRepository,
        private TargetImageStorage $targetImageStorage,
    ) {
    }

    public function __invoke(UpdateTarget $command): CommandResult
    {
        $archeryGround = $this->archeryGroundRepository->find($command->archeryGroundId);
        if (! $archeryGround instanceof ArcheryGround) {
            return CommandResult::failure('Archery ground not found.');
        }

        $existingImage = null;
        $targetExists  = false;
        foreach ($archeryGround->targetStorage() as $target) {
            if ($target->id() === $command->targetId) {
                $existingImage = $target->image();
                $targetExists  = true;
                break;
            }
        }

        if (! $targetExists) {
            return CommandResult::failure('Target not found.');
        }

        $newImagePath = null;
        if ($command->image instanceof UploadedFile) {
            $newImagePath = $this->targetImageStorage->store($command->image, $command->targetId);
        }

        $this->archeryGroundRepository->updateTarget(
            $command->archeryGroundId,
            $command->targetId,
            $command->name,
            $command->type->value,
            $newImagePath,
        );

        if ($newImagePath !== null && $existingImage !== null && $existingImage !== $newImagePath) {
            $this->targetImageStorage->remove($existingImage);
        }

        return CommandResult::success('Target "' . $command->name . '" was updated successfully.');
    }
}
