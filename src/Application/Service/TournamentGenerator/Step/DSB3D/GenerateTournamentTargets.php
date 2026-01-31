<?php

declare(strict_types=1);

namespace App\Application\Service\TournamentGenerator\Step\DSB3D;

use App\Application\Service\TournamentGenerator\DTO\TournamentResult;
use App\Application\Service\TournamentGenerator\Exception\TournamentGenerationFailed;
use App\Application\Service\TournamentGenerator\Step\TournamentGenerationStep;
use App\Domain\Entity\ArcheryGround\ShootingLane;
use App\Domain\Entity\ArcheryGround\Target;
use App\Domain\Entity\TournamentTarget;
use App\Domain\Entity\TournamentTargetCollection;
use App\Domain\ValueObject\Ruleset;
use App\Domain\ValueObject\StakeDistances;
use App\Domain\ValueObject\TargetType;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

use function ceil;
use function floor;
use function max;
use function min;
use function random_int;
use function shuffle;

#[AsTaggedItem(priority: 460)]
final readonly class GenerateTournamentTargets implements TournamentGenerationStep
{
    public function __construct(private readonly LoggerInterface $logger)
    {
    }

    public function getName(): string
    {
        return 'Generate Tournament Targets with Stake Distances';
    }

    public function supports(TournamentResult $tournamentResult): bool
    {
        return true;
    }

    public function process(TournamentResult $tournamentResult): void
    {
        if ($tournamentResult->selectedLanesPerTargetGroup === []) {
            throw new TournamentGenerationFailed('No lanes and targets have been selected to generate tournament targets.');
        }

        $laneAssignments = $this->buildLaneAssignments($tournamentResult);
        if ($laneAssignments === []) {
            throw new TournamentGenerationFailed('No lane assignments available to generate tournament targets.');
        }

        $targets          = new TournamentTargetCollection();
        $remainingTargets = $tournamentResult->numberOfTargets;
        $rounds           = $tournamentResult->requiredRounds;

        for ($round = 1; $round <= $rounds; $round++) {
            shuffle($laneAssignments);
            foreach ($laneAssignments as $assignment) {
                if ($remainingTargets <= 0) {
                    break 2;
                }

                $stakes = $tournamentResult->randomizeStakesBetweenRounds
                    ? $this->generateStakeDistances(
                        ruleset: $tournamentResult->ruleset,
                        targetType: $assignment['targetType'],
                        lane: $assignment['lane'],
                    )
                    : $assignment['stakes'];

                $targets->add(new TournamentTarget(
                    round: $round,
                    shootingLane: $assignment['lane'],
                    target: $assignment['target'],
                    distance: $this->determineTargetDistance($stakes),
                    stakes: $stakes,
                ));

                $remainingTargets--;
            }
        }

        $tournamentResult->targets = $targets;

        $this->logger->debug('Generated tournament targets with stake distances.', [
            'rounds' => $rounds,
            'targets_generated' => $targets->count(),
            'requested_targets' => $tournamentResult->numberOfTargets,
        ]);
    }

    /** @return list<array{lane: ShootingLane, target: Target, targetType: TargetType, stakes: StakeDistances}> */
    private function buildLaneAssignments(TournamentResult $tournamentResult): array
    {
        $assignments = [];

        foreach ($tournamentResult->selectedLanesPerTargetGroup as $laneConfiguration) {
            $targetType = $laneConfiguration['type'];
            foreach ($laneConfiguration['lanes'] as $laneConfig) {
                $lane   = $laneConfig['lane'];
                $target = $laneConfig['target'];

                if (! $target instanceof Target) {
                    throw new TournamentGenerationFailed('Targets have not been assigned to all selected lanes.');
                }

                $assignments[] = [
                    'lane' => $lane,
                    'target' => $target,
                    'targetType' => $targetType,
                    'stakes' => $this->generateStakeDistances(
                        ruleset: $tournamentResult->ruleset,
                        targetType: $targetType,
                        lane: $lane,
                    ),
                ];
            }
        }

        return $assignments;
    }

    private function generateStakeDistances(Ruleset $ruleset, TargetType $targetType, ShootingLane $lane): StakeDistances
    {
        $ranges = $ruleset->stakeDistanceRanges($targetType);
        $stakes = [];

        foreach ($ranges as $stake => $range) {
            $minDistance = (float) $range['min'];
            $maxDistance = (float) $range['max'];
            $laneMax     = $lane->maxDistance();
            $allowedMax  = min($maxDistance, $laneMax);

            $minInt = (int) ceil($minDistance);
            $maxInt = (int) floor($allowedMax);

            if ($maxInt < $minInt) {
                throw new TournamentGenerationFailed(
                    'Lane "' . $lane->name() . '" cannot satisfy stake "' . $stake . '" for target type "' . $targetType->name . '".',
                );
            }

            $stakes[$stake] = random_int($minInt, $maxInt);
        }

        return new StakeDistances($stakes);
    }

    private function determineTargetDistance(StakeDistances $stakes): int
    {
        return max($stakes->all());
    }
}
