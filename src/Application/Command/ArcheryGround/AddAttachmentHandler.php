<?php

declare(strict_types=1);

namespace App\Application\Command\ArcheryGround;

use App\Application\Command\CommandResult;
use App\Application\Service\AttachmentStorage;
use App\Domain\Entity\ArcheryGround\Attachment;
use App\Domain\Repository\ArcheryGroundRepository;
use RuntimeException;

final readonly class AddAttachmentHandler
{
    public function __construct(
        private ArcheryGroundRepository $archeryGroundRepository,
        private AttachmentStorage $attachmentStorage,
    ) {
    }

    public function __invoke(AddAttachment $command): CommandResult
    {
        $attachmentId = $this->archeryGroundRepository->nextIdentity();

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

        $this->archeryGroundRepository->addAttachment($command->archeryGroundId, $attachment);

        return CommandResult::success('The attachment "' . $command->title . '" was added.');
    }
}
