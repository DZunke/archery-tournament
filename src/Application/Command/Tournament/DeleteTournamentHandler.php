<?php

declare(strict_types=1);

namespace App\Application\Command\Tournament;

use App\Application\Command\CommandResult;
use App\Application\Service\AttachmentStorage;
use App\Domain\Entity\Tournament;
use App\Domain\Repository\TournamentRepository;

final readonly class DeleteTournamentHandler
{
    public function __construct(
        private TournamentRepository $tournamentRepository,
        private AttachmentStorage $attachmentStorage,
    ) {
    }

    public function __invoke(DeleteTournament $command): CommandResult
    {
        $tournament     = $this->tournamentRepository->find($command->tournamentId);
        $tournamentName = $tournament instanceof Tournament ? $tournament->name() : 'Unknown';

        if ($tournament instanceof Tournament) {
            foreach ($tournament->attachments() as $attachment) {
                $this->attachmentStorage->remove($attachment->filePath());
            }
        }

        $this->tournamentRepository->delete($command->tournamentId);

        return CommandResult::success('The tournament "' . $tournamentName . '" was deleted.');
    }
}
