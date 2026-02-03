<?php

declare(strict_types=1);

namespace App\Application\Command\Tournament;

use App\Application\Command\CommandResult;
use App\Application\Service\TournamentGenerator\DTO\TournamentGenerationRequest;
use App\Application\Service\TournamentGenerator\Exception\TournamentGenerationFailed;
use App\Application\Service\TournamentGenerator\TournamentGenerationPipeline;
use App\Domain\Entity\ArcheryGround;
use App\Domain\Entity\Tournament;
use App\Domain\Entity\TournamentTargetCollection;
use App\Domain\Repository\ArcheryGroundRepository;
use App\Domain\Repository\TournamentRepository;

final readonly class CreateTournamentHandler
{
    public function __construct(
        private TournamentRepository $tournamentRepository,
        private ArcheryGroundRepository $archeryGroundRepository,
        private TournamentGenerationPipeline $tournamentGenerationPipeline,
    ) {
    }

    public function __invoke(CreateTournament $command): CommandResult
    {
        $archeryGround = $this->archeryGroundRepository->find($command->archeryGroundId);
        if (! $archeryGround instanceof ArcheryGround) {
            return CommandResult::failure('Archery ground not found.');
        }

        $tournament = new Tournament(
            id: $this->tournamentRepository->nextIdentity(),
            name: $command->name,
            eventDate: $command->eventDate,
            ruleset: $command->ruleset,
            archeryGround: $archeryGround,
            numberOfTargets: $command->numberOfTargets,
            targets: new TournamentTargetCollection(),
        );

        if ($command->autoGenerate) {
            try {
                $generated = $this->tournamentGenerationPipeline->generate(
                    new TournamentGenerationRequest(
                        archeryGround: $archeryGround,
                        ruleset: $command->ruleset,
                        amountOfTargets: $command->numberOfTargets,
                        randomizeStakesBetweenRounds: $command->randomizeStakesBetweenRounds,
                    ),
                );
                $tournament->replaceTargets($generated->targets());
            } catch (TournamentGenerationFailed $exception) {
                return CommandResult::failure($exception->getMessage());
            }
        }

        $this->tournamentRepository->save($tournament);

        return CommandResult::success('Tournament created.', ['id' => $tournament->id()]);
    }
}
