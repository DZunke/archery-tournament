<?php

declare(strict_types=1);

namespace App\Application\Command\Tournament;

use App\Application\Command\CommandResult;
use App\Application\Service\AttachmentStorage;
use App\Domain\Entity\Tournament;
use App\Domain\Repository\TournamentRepository;

final readonly class RemoveAttachmentHandler
{
    public function __construct(
        private TournamentRepository $tournamentRepository,
        private AttachmentStorage $attachmentStorage,
    ) {
    }

    public function __invoke(RemoveAttachment $command): CommandResult
    {
        $tournament = $this->tournamentRepository->find($command->tournamentId);
        if (! $tournament instanceof Tournament) {
            return CommandResult::failure('Tournament not found.');
        }

        $filePath       = null;
        $attachmentName = 'Unknown';
        foreach ($tournament->attachments() as $attachment) {
            if ($attachment->id() === $command->attachmentId) {
                $filePath       = $attachment->filePath();
                $attachmentName = $attachment->title();
                break;
            }
        }

        if ($filePath !== null) {
            $this->attachmentStorage->remove($filePath);
        }

        $this->tournamentRepository->removeAttachment($command->attachmentId);

        return CommandResult::success('The attachment "' . $attachmentName . '" was removed.');
    }
}
