<?php

declare(strict_types=1);

namespace App\Application\Command\Tournament;

use App\Application\Command\CommandResult;
use App\Application\Service\TournamentValidation\TournamentValidationContext;
use App\Application\Service\TournamentValidation\TournamentValidator;
use App\Domain\Entity\ArcheryGround\ShootingLane;
use App\Domain\Entity\ArcheryGround\Target;
use App\Domain\Entity\Tournament;
use App\Domain\Entity\TournamentTarget;
use App\Domain\Entity\TournamentTargetCollection;
use App\Domain\Repository\TournamentRepository;
use App\Domain\ValueObject\StakeDistances;

final readonly class UpdateTournamentTargetsHandler
{
    public function __construct(
        private TournamentRepository $tournamentRepository,
        private TournamentValidator $tournamentValidator,
    ) {
    }

    public function __invoke(UpdateTournamentTargets $command): CommandResult
    {
        $tournament = $this->tournamentRepository->find($command->tournamentId);
        if (! $tournament instanceof Tournament) {
            return CommandResult::failure('Tournament not found.');
        }

        $archeryGround    = $tournament->archeryGround();
        $context          = TournamentValidationContext::fromDraftAssignments(
            ruleset: $tournament->ruleset(),
            expectedTargetCount: $tournament->numberOfTargets(),
            assignments: $command->assignments,
            lanes: $archeryGround->shootingLanes(),
            targets: $archeryGround->targetStorage(),
        );
        $validationResult = $this->tournamentValidator->validate($context);

        if (! $validationResult->isValid()) {
            return CommandResult::failure('Tournament assignments failed validation.', [
                'issues' => $validationResult->issues(),
            ]);
        }

        $collection = new TournamentTargetCollection();

        foreach ($context->assignments as $assignment) {
            $lane   = $assignment->lane;
            $target = $assignment->target;
            if (! $lane instanceof ShootingLane) {
                continue;
            }

            if (! $target instanceof Target) {
                continue;
            }

            $stakeDistances = new StakeDistances($assignment->stakes);

            $collection->add(new TournamentTarget(
                round: $assignment->round,
                shootingLane: $lane,
                target: $target,
                distance: $stakeDistances->max(),
                stakes: $stakeDistances,
            ));
        }

        $tournament->replaceTargets($collection);
        $this->tournamentRepository->save($tournament);

        return CommandResult::success('Tournament assignments saved.');
    }
}
