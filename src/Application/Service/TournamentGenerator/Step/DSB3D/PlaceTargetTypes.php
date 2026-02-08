<?php

declare(strict_types=1);

namespace App\Application\Service\TournamentGenerator\Step\DSB3D;

use App\Application\Service\TournamentGenerator\DTO\TournamentResult;
use App\Application\Service\TournamentGenerator\Exception\TournamentGenerationFailed;
use App\Application\Service\TournamentGenerator\Step\TournamentGenerationStep;
use App\Domain\Entity\ArcheryGround\ShootingLane;
use App\Domain\Entity\ArcheryGround\Target;
use App\Domain\ValueObject\TargetType;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

use function array_fill_keys;
use function array_filter;
use function array_map;
use function array_slice;
use function array_splice;
use function array_values;
use function ceil;
use function count;
use function min;
use function shuffle;

#[AsTaggedItem(priority: 480)]
final readonly class PlaceTargetTypes implements TournamentGenerationStep
{
    public function __construct(private readonly LoggerInterface $logger)
    {
    }

    public function getName(): string
    {
        return 'Place Target Types at qualified shooting lanes';
    }

    public function supports(TournamentResult $tournamentResult): bool
    {
        return true;
    }

    public function process(TournamentResult $tournamentResult): void
    {
        if (! $tournamentResult->ruleset->supportsTargetGroupBalancing()) {
            $this->placeFreehandTargets($tournamentResult);

            return;
        }

        $roundsThatWillBeDone = $tournamentResult->requiredRounds;
        $numberOfTargets      = $tournamentResult->numberOfTargets;
        $amountOfTargetTypes  = count($tournamentResult->ruleset->requiredTargetTypes());

        $amountPerTargetTypeToBePlaced = (int) ceil($numberOfTargets / $amountOfTargetTypes / $roundsThatWillBeDone);
        $this->logger->debug('We need to place ' . $amountPerTargetTypeToBePlaced . ' targets of each type per round.');

        $completeAmountOfRequiredLanes = $amountPerTargetTypeToBePlaced * $amountOfTargetTypes;
        $this->logger->debug('There are minimum ' . $completeAmountOfRequiredLanes . ' lanes required to place all target types per round.');

        $byMinDistanceOrderedTargetTypes = $tournamentResult->ruleset->targetTypesOrderedByMaxDistance();
        $availableLanes                  = $tournamentResult->availableLanes;
        foreach ($byMinDistanceOrderedTargetTypes as $targetType) {
            $minDistance = $tournamentResult->ruleset->getRequiredMinStakeDistance($targetType);
            $this->logger->debug('Placing target type "' . $targetType->name . '" with required minimum distance ' . $minDistance . ' meters.');

            // Randomize the left available lanes
            shuffle($availableLanes);

            // Find qualified lanes for this target type based on the max distance and remove them from the available lanes
            $qualifiedLanesForTargetType = [];
            foreach ($availableLanes as $key => $lane) {
                if ($lane->maxDistance() < $minDistance) {
                    continue;
                }

                $this->logger->debug('Found qualified lane "' . $lane->name() . '" for target type "' . $targetType->name . '".');

                $qualifiedLanesForTargetType[] = $lane;
                unset($availableLanes[$key]);

                if (count($qualifiedLanesForTargetType) >= $amountPerTargetTypeToBePlaced) {
                    break;
                }
            }

            // Check there are enough lanes assigned for this target type
            if (count($qualifiedLanesForTargetType) < $amountPerTargetTypeToBePlaced) {
                throw new TournamentGenerationFailed('Not enough qualified lanes to place target type "' . $targetType->name . '". Required: ' . $amountPerTargetTypeToBePlaced . ', Found: ' . count($qualifiedLanesForTargetType) . '.');
            }

            $tournamentResult->selectedLanesPerTargetGroup[$targetType->value] = [
                'type' => $targetType,
                'lanes' => array_map(
                    static fn (ShootingLane $lane): array => ['lane' => $lane, 'target' => null],
                    $qualifiedLanesForTargetType,
                ),
            ];
        }
    }

    private function placeFreehandTargets(TournamentResult $tournamentResult): void
    {
        $availableLanes   = $tournamentResult->availableLanes;
        $availableTargets = $tournamentResult->archeryGround->targetStorage();

        // Filter out training-only targets unless explicitly included
        if (! $tournamentResult->includeTrainingOnly) {
            $availableTargets = array_values(array_filter(
                $availableTargets,
                static fn (Target $target) => ! $target->forTrainingOnly(),
            ));
        }

        if (count($availableTargets) === 0) {
            throw new TournamentGenerationFailed('No targets are available for freehand tournament generation.');
        }

        if (count($availableLanes) === 0) {
            throw new TournamentGenerationFailed('No lanes are available for freehand tournament generation.');
        }

        $lanesToUse = min(
            $tournamentResult->numberOfTargets,
            count($availableLanes),
            count($availableTargets),
        );

        if ($lanesToUse <= 0) {
            throw new TournamentGenerationFailed('Not enough lanes or targets to generate a freehand tournament.');
        }

        $typePool = array_map(
            static fn (Target $target): TargetType => $target->type(),
            $availableTargets,
        );

        shuffle($typePool);
        $typePool = array_slice($typePool, 0, $lanesToUse);
        $counts   = array_fill_keys(array_map(static fn (TargetType $type): string => $type->value, $typePool), 0);

        foreach ($typePool as $type) {
            $counts[$type->value]++;
        }

        shuffle($availableLanes);

        foreach ($counts as $typeValue => $count) {
            if ($count <= 0) {
                continue;
            }

            $lanes = array_splice($availableLanes, 0, $count);
            $type  = TargetType::from($typeValue);

            $tournamentResult->selectedLanesPerTargetGroup[$typeValue] = [
                'type' => $type,
                'lanes' => array_map(
                    static fn (ShootingLane $lane): array => ['lane' => $lane, 'target' => null],
                    $lanes,
                ),
            ];
        }

        if ($tournamentResult->selectedLanesPerTargetGroup === []) {
            throw new TournamentGenerationFailed('Unable to assign lanes for freehand target placement.');
        }
    }
}
