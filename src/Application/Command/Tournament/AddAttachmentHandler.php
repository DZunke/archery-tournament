<?php

declare(strict_types=1);

namespace App\Application\Command\Tournament;

use App\Application\Command\CommandResult;
use App\Application\Service\AttachmentStorage;
use App\Domain\Entity\Tournament\Attachment;
use App\Domain\Repository\TournamentRepository;
use RuntimeException;

final readonly class AddAttachmentHandler
{
    public function __construct(
        private TournamentRepository $tournamentRepository,
        private AttachmentStorage $attachmentStorage,
    ) {
    }

    public function __invoke(AddAttachment $command): CommandResult
    {
        $attachmentId = $this->tournamentRepository->nextIdentity();

        // Extract metadata BEFORE storing, as store() moves the temp file
        $mimeType         = $command->file->getMimeType() ?? 'application/octet-stream';
        $originalFilename = $command->file->getClientOriginalName();

        try {
            $filePath = $this->attachmentStorage->store($command->file, $attachmentId);
        } catch (RuntimeException $exception) {
            return CommandResult::failure($exception->getMessage());
        }

        $attachment = new Attachment(
            id: $attachmentId,
            title: $command->title,
            filePath: $filePath,
            mimeType: $mimeType,
            originalFilename: $originalFilename,
        );

        $this->tournamentRepository->addAttachment($command->tournamentId, $attachment);

        return CommandResult::success('The attachment "' . $command->title . '" was added.');
    }
}
