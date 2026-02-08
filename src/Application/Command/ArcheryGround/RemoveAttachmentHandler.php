<?php

declare(strict_types=1);

namespace App\Application\Command\ArcheryGround;

use App\Application\Command\CommandResult;
use App\Application\Service\AttachmentStorage;
use App\Domain\Entity\ArcheryGround;
use App\Domain\Repository\ArcheryGroundRepository;

final readonly class RemoveAttachmentHandler
{
    public function __construct(
        private ArcheryGroundRepository $archeryGroundRepository,
        private AttachmentStorage $attachmentStorage,
    ) {
    }

    public function __invoke(RemoveAttachment $command): CommandResult
    {
        $archeryGround = $this->archeryGroundRepository->find($command->archeryGroundId);
        if (! $archeryGround instanceof ArcheryGround) {
            return CommandResult::failure('Archery ground not found.');
        }

        $filePath       = null;
        $attachmentName = 'Unknown';
        foreach ($archeryGround->attachments() as $attachment) {
            if ($attachment->id() === $command->attachmentId) {
                $filePath       = $attachment->filePath();
                $attachmentName = $attachment->title();
                break;
            }
        }

        if ($filePath !== null) {
            $this->attachmentStorage->remove($filePath);
        }

        $this->archeryGroundRepository->removeAttachment($command->attachmentId);

        return CommandResult::success('The attachment "' . $attachmentName . '" was removed.');
    }
}
