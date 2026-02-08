<?php

declare(strict_types=1);

namespace App\Application\Command\ArcheryGround;

use App\Application\Command\CommandResult;
use App\Application\Service\AttachmentStorage;
use App\Application\Service\TargetImageStorage;
use App\Domain\Entity\ArcheryGround;
use App\Domain\Repository\ArcheryGroundRepository;

final readonly class DeleteArcheryGroundHandler
{
    public function __construct(
        private ArcheryGroundRepository $archeryGroundRepository,
        private TargetImageStorage $targetImageStorage,
        private AttachmentStorage $attachmentStorage,
    ) {
    }

    public function __invoke(DeleteArcheryGround $command): CommandResult
    {
        $archeryGround = $this->archeryGroundRepository->find($command->id);
        if ($archeryGround instanceof ArcheryGround) {
            foreach ($archeryGround->targetStorage() as $target) {
                $this->targetImageStorage->remove($target->image());
            }

            foreach ($archeryGround->attachments() as $attachment) {
                $this->attachmentStorage->remove($attachment->filePath());
            }
        }

        $groundName = $archeryGround instanceof ArcheryGround ? $archeryGround->name() : 'Unknown';

        $this->archeryGroundRepository->delete($command->id);

        return CommandResult::success('The archery ground "' . $groundName . '" was deleted.');
    }
}
