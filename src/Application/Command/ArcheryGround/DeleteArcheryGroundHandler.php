<?php

declare(strict_types=1);

namespace App\Application\Command\ArcheryGround;

use App\Application\Command\CommandResult;
use App\Application\Service\TargetImageStorage;
use App\Domain\Entity\ArcheryGround;
use App\Domain\Repository\ArcheryGroundRepository;

final readonly class DeleteArcheryGroundHandler
{
    public function __construct(
        private ArcheryGroundRepository $archeryGroundRepository,
        private TargetImageStorage $targetImageStorage,
    ) {
    }

    public function __invoke(DeleteArcheryGround $command): CommandResult
    {
        $archeryGround = $this->archeryGroundRepository->find($command->id);
        if ($archeryGround instanceof ArcheryGround) {
            foreach ($archeryGround->targetStorage() as $target) {
                $this->targetImageStorage->remove($target->image());
            }
        }

        $groundName = $archeryGround instanceof ArcheryGround ? $archeryGround->name() : 'Unknown';

        $this->archeryGroundRepository->delete($command->id);

        return CommandResult::success('The archery ground "' . $groundName . '" was deleted.');
    }
}
