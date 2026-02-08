<?php

declare(strict_types=1);

namespace App\Application\Command\ArcheryGround;

use App\Application\Command\CommandResult;
use App\Application\Service\TargetImageStorage;
use App\Domain\Entity\ArcheryGround;
use App\Domain\Repository\ArcheryGroundRepository;
use App\Domain\Repository\TournamentRepository;

use function count;
use function implode;

final readonly class RemoveTargetHandler
{
    public function __construct(
        private ArcheryGroundRepository $archeryGroundRepository,
        private TournamentRepository $tournamentRepository,
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

        // Check if target is used in any tournament
        $tournamentNames = $this->tournamentRepository->findTournamentNamesUsingTarget($command->targetId);
        if (count($tournamentNames) > 0) {
            return CommandResult::failure(
                'Cannot remove target "' . $targetName . '" because it is used in tournament(s): ' . implode(', ', $tournamentNames) . '.',
            );
        }

        if ($imagePath !== null) {
            $this->targetImageStorage->remove($imagePath);
        }

        $this->archeryGroundRepository->removeTarget($command->targetId);

        return CommandResult::success('The target "' . $targetName . '" was removed.');
    }
}
