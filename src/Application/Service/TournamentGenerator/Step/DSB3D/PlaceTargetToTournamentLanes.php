<?php

declare(strict_types=1);

namespace App\Application\Service\TournamentGenerator\Step\DSB3D;

use App\Application\Service\TournamentGenerator\DTO\TournamentResult;
use App\Application\Service\TournamentGenerator\Exception\TournamentGenerationFailed;
use App\Application\Service\TournamentGenerator\Step\TournamentGenerationStep;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

#[AsTaggedItem(priority: 470)]
final class PlaceTargetToTournamentLanes implements TournamentGenerationStep
{
    public function __construct(private readonly LoggerInterface $logger)
    {
    }

    public function getName(): string
    {
        return 'Place Targets to selected Tournament Lanes';
    }

    public function supports(TournamentResult $tournamentResult): bool
    {
        return true;
    }

    public function process(TournamentResult $tournamentResult): void
    {
        if ($tournamentResult->selectedLanesPerTargetGroup === []) {
            throw new TournamentGenerationFailed('No lanes have been selected for target placement.');
        }


        foreach ($tournamentResult->selectedLanesPerTargetGroup as $groupIdentifier => $laneConfiguration) {
            $targetType = $laneConfiguration['type'];
            $availableTargets = $tournamentResult->archeryGround->targetStorageByType($targetType);

            if (count($laneConfiguration['lanes']) > count($availableTargets)) {
                throw new TournamentGenerationFailed('Not enough targets for target type "' . $targetType->name . '" available at shooting range.');
            }

            \shuffle($availableTargets);
            foreach ($laneConfiguration['lanes'] as $index => $singleLaneConfig) {
                $assignedTarget = \array_pop($availableTargets);
                $tournamentResult->selectedLanesPerTargetGroup[$groupIdentifier]['lanes'][$index]['target'] = $assignedTarget;

                shuffle($availableTargets);
            }
        }
    }
}
