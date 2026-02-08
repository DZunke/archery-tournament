<?php

declare(strict_types=1);

namespace App\Application\Command\ArcheryGround;

use App\Application\Command\CommandResult;
use App\Application\Service\TargetImageStorage;
use App\Domain\Entity\ArcheryGround\Target;
use App\Domain\Repository\ArcheryGroundRepository;
use RuntimeException;

final readonly class AddTargetHandler
{
    public function __construct(
        private ArcheryGroundRepository $archeryGroundRepository,
        private TargetImageStorage $targetImageStorage,
    ) {
    }

    public function __invoke(AddTarget $command): CommandResult
    {
        $targetId = $this->archeryGroundRepository->nextIdentity();

        try {
            $imagePath = $this->targetImageStorage->store($command->image, $targetId);
        } catch (RuntimeException $exception) {
            return CommandResult::failure($exception->getMessage());
        }

        $target = new Target(
            id: $targetId,
            type: $command->type,
            name: $command->name,
            image: $imagePath,
        );

        $this->archeryGroundRepository->addTarget($command->archeryGroundId, $target);

        return CommandResult::success('The target "' . $command->name . '" was added.');
    }
}
