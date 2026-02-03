<?php

declare(strict_types=1);

namespace App\Application\Command\Tournament;

use App\Application\Command\CommandResult;
use App\Application\Service\TournamentGenerator\DTO\TournamentGenerationRequest;
use App\Application\Service\TournamentGenerator\Exception\TournamentGenerationFailed;
use App\Application\Service\TournamentGenerator\TournamentGenerationPipeline;
use App\Domain\Entity\Tournament;
use App\Domain\Repository\TournamentRepository;

final readonly class RegenerateTournamentHandler
{
    public function __construct(
        private TournamentRepository $tournamentRepository,
        private TournamentGenerationPipeline $tournamentGenerationPipeline,
    ) {
    }

    public function __invoke(RegenerateTournament $command): CommandResult
    {
        $tournament = $this->tournamentRepository->find($command->tournamentId);
        if (! $tournament instanceof Tournament) {
            return CommandResult::failure('Tournament not found.');
        }

        try {
            $generated = $this->tournamentGenerationPipeline->generate(
                new TournamentGenerationRequest(
                    archeryGround: $tournament->archeryGround(),
                    ruleset: $tournament->ruleset(),
                    amountOfTargets: $tournament->numberOfTargets(),
                    randomizeStakesBetweenRounds: $command->randomizeStakesBetweenRounds,
                ),
            );
        } catch (TournamentGenerationFailed $exception) {
            return CommandResult::failure($exception->getMessage());
        }

        $tournament->replaceTargets($generated->targets());
        $this->tournamentRepository->save($tournament);

        return CommandResult::success('Tournament regenerated.');
    }
}
