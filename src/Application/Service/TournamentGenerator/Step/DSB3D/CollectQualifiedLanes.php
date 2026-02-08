<?php

declare(strict_types=1);

namespace App\Application\Service\TournamentGenerator\Step\DSB3D;

use App\Application\Service\TournamentGenerator\DTO\TournamentResult;
use App\Application\Service\TournamentGenerator\Exception\NotEnoughLanesAtShootingRange;
use App\Application\Service\TournamentGenerator\Step\TournamentGenerationStep;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

use function array_filter;
use function array_values;
use function count;

#[AsTaggedItem(priority: 500)]
final readonly class CollectQualifiedLanes implements TournamentGenerationStep
{
    public function __construct(private LoggerInterface $logger)
    {
    }

    public function getName(): string
    {
        return 'Collect Qualified Lanes at Archery Ground';
    }

    public function supports(TournamentResult $tournamentResult): bool
    {
        return true;
    }

    public function process(TournamentResult $tournamentResult): void
    {
        $availableShootingLanes = $tournamentResult->archeryGround->shootingLanes();

        if (count($availableShootingLanes) === 0) {
            throw NotEnoughLanesAtShootingRange::noLanesAtShootingRange();
        }

        // Filter out training-only lanes unless explicitly included
        if (! $tournamentResult->includeTrainingOnly) {
            $availableShootingLanes = array_filter(
                $availableShootingLanes,
                static fn ($lane) => ! $lane->forTrainingOnly(),
            );

            $this->logger->debug(
                'Excluded training-only lanes from selection.',
                ['remaining_lanes' => count($availableShootingLanes)],
            );
        }

        $minTournamentRequiredRange = $tournamentResult->ruleset->getOverallMinStakeDistance();
        $this->logger->debug(
            'Minimum required stake distance for tournament is ' . $minTournamentRequiredRange . ' meters.',
            ['min_required_stake_distance' => $minTournamentRequiredRange],
        );

        // Filter the available shooting lanes that their maximum distance is >= the minimum required distance
        $qualifiedLanes                   = array_filter(
            $availableShootingLanes,
            static fn ($lane) => $lane->maxDistance() >= $minTournamentRequiredRange,
        );
        $tournamentResult->availableLanes = array_values($qualifiedLanes);

        $this->logger->debug(
            'Collected ' . count($tournamentResult->availableLanes) . ' qualified lanes for the tournament.',
            [
                'qualified_lanes_count' => count($tournamentResult->availableLanes),
                'total_available_lanes' => count($availableShootingLanes),
            ],
        );
    }
}
