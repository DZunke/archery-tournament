<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Domain\Entity\ArcheryGround\ShootingLane;
use App\Domain\Entity\ArcheryGround\Target;
use App\Domain\Entity\Tournament;
use App\Domain\Entity\TournamentTarget;
use App\Domain\Entity\TournamentTargetCollection;
use App\Domain\ValueObject\Ruleset;
use App\Domain\ValueObject\StakeDistances;
use App\Domain\ValueObject\TargetType;
use RuntimeException;
use Webmozart\Assert\Assert;

use function array_filter;
use function array_map;
use function array_slice;
use function array_sum;
use function array_values;
use function ceil;
use function count;
use function floor;
use function in_array;
use function intdiv;
use function max;
use function min;
use function random_int;
use function shuffle;
use function spl_object_id;
use function sprintf;
use function usort;

use const COUNT_RECURSIVE;

final class TournamentRandomCalculator
{
    public function calculate(Tournament $tournament): void
    {
        $this->guardPreconditions($tournament);

        $ruleset       = $tournament->ruleset();
        $shootingLanes = $tournament->archeryGround()->shootingLanes();
        $requiredTypes = $ruleset->requiredTargetTypes();

        [$targetsByType, $targetSupplyByType] = $this->groupTargetsByType(
            $tournament,
            $requiredTypes,
        );

        $compatibleLanes  = $this->buildCompatibleLanes($shootingLanes, $requiredTypes, $ruleset);
        $perRoundCapacity = $this->determinePerRoundCapacity($tournament, $compatibleLanes, $targetsByType);
        $selectedLaneSet  = $this->selectLanes($compatibleLanes, $perRoundCapacity);
        $typeRequirements = $this->distributeTypeCounts($tournament, $requiredTypes);

        $assignments = $this->buildAssignments(
            $selectedLaneSet,
            $requiredTypes,
            $targetsByType,
            $targetSupplyByType,
            $typeRequirements,
            $perRoundCapacity,
        );

        $tournament->replaceTargets($assignments);
    }

    private function guardPreconditions(Tournament $tournament): void
    {
        Assert::notEmpty($tournament->archeryGround()->shootingLanes(), 'The archery ground must have at least one shooting lane.');
        Assert::greaterThanEq(
            $tournament->numberOfTargets(),
            count($tournament->ruleset()->requiredTargetTypes()),
            'The tournament must request at least one target per required target group.',
        );
        Assert::same(0, count($tournament->targets()), 'Tournament already contains targets.');
    }

    /**
     * @param list<TargetType> $requiredTypes
     *
     * @return array{array<string,list<Target>>, array<string,int>}
     */
    private function groupTargetsByType(Tournament $tournament, array $requiredTypes): array
    {
        $availableTargets = array_values(array_filter(
            $tournament->archeryGround()->targetStorage(),
            static fn (Target $target): bool => in_array($target->type(), $requiredTypes, true),
        ));
        Assert::notEmpty($availableTargets, 'The archery ground must have targets fitting the ruleset.');

        shuffle($availableTargets);
        $targetsByType      = [];
        $targetSupplyByType = [];

        foreach ($availableTargets as $target) {
            $typeKey                      = $target->type()->value;
            $targetsByType[$typeKey]    ??= [];
            $targetsByType[$typeKey][]    = $target;
            $targetSupplyByType[$typeKey] = ($targetSupplyByType[$typeKey] ?? 0) + 1;
        }

        foreach ($requiredTypes as $requiredType) {
            Assert::keyExists(
                $targetsByType,
                $requiredType->value,
                sprintf('At least one target of type %s is required.', $requiredType->value),
            );
        }

        return [$targetsByType, $targetSupplyByType];
    }

    /**
     * @param list<ShootingLane> $shootingLanes
     * @param list<TargetType>   $requiredTypes
     *
     * @return list<array{lane:ShootingLane, compatible:array<string,array{stakeRanges:array<string,array{min:float,max:float}>, farthestStake:string}>}>
     */
    private function buildCompatibleLanes(array $shootingLanes, array $requiredTypes, Ruleset $ruleset): array
    {
        $compatibleLanes = [];

        foreach ($shootingLanes as $lane) {
            $compatibles = [];
            foreach ($requiredTypes as $requiredType) {
                $stakeRanges   = $ruleset->stakeDistanceRanges($requiredType);
                $farthestStake = null;
                $farthestRange = null;

                foreach ($stakeRanges as $stake => $range) {
                    if ($farthestRange !== null && $range['max'] <= $farthestRange['max']) {
                        continue;
                    }

                    $farthestRange = $range;
                    $farthestStake = $stake;
                }

                if ($farthestRange === null) {
                    continue;
                }

                if ($farthestRange['max'] > $lane->maxDistance()) {
                    continue;
                }

                $compatibles[$requiredType->value] = [
                    'stakeRanges' => $stakeRanges,
                    'farthestStake' => $farthestStake,
                ];
            }

            Assert::notEmpty(
                $compatibles,
                sprintf('Shooting lane %s is incompatible with all required target types.', $lane->name()),
            );

            $compatibleLanes[] = [
                'lane' => $lane,
                'compatible' => $compatibles,
            ];
        }

        Assert::notEmpty($compatibleLanes, 'No compatible shooting lanes available for required target types.');

        usort(
            $compatibleLanes,
            static function (array $a, array $b): int {
                $countA = count($a['compatible']);
                $countB = count($b['compatible']);

                if ($countA === $countB) {
                    return $b['lane']->maxDistance() <=> $a['lane']->maxDistance();
                }

                return $countB <=> $countA;
            },
        );

        return $compatibleLanes;
    }

    /**
     * @param list<array{lane:ShootingLane, compatible:array<string,mixed>}> $compatibleLanes
     * @param array<string,list<Target>>                                     $targetsByType
     */
    private function determinePerRoundCapacity(Tournament $tournament, array $compatibleLanes, array $targetsByType): int
    {
        $maxUsableLanes = max(1, count($compatibleLanes) - 1);

        $capacity = min(
            $tournament->numberOfTargets(),
            $maxUsableLanes,
            count($compatibleLanes),
            count($targetsByType, COUNT_RECURSIVE) > 0 ? array_sum(array_map(count(...), $targetsByType)) : 0,
        );

        Assert::greaterThan($capacity, 0, 'The tournament must allow at least one target per round.');

        return $capacity;
    }

    /**
     * @param list<array{lane:ShootingLane, compatible:array<string,mixed>}> $compatibleLanes
     *
     * @return list<array{lane:ShootingLane, compatible:array<string,mixed>}>
     */
    private function selectLanes(array $compatibleLanes, int $perRoundCapacity): array
    {
        return array_slice($compatibleLanes, 0, $perRoundCapacity);
    }

    /**
     * @param list<TargetType> $requiredTypes
     *
     * @return array<string,int>
     */
    private function distributeTypeCounts(Tournament $tournament, array $requiredTypes): array
    {
        $typeCount   = count($requiredTypes);
        $basePerType = intdiv($tournament->numberOfTargets(), $typeCount);
        $withExtra   = $tournament->numberOfTargets() % $typeCount;
        $counts      = [];

        foreach ($requiredTypes as $index => $requiredType) {
            $counts[$requiredType->value] = $basePerType + ($index < $withExtra ? 1 : 0);
        }

        return $counts;
    }

    /**
     * @param list<array{lane:ShootingLane, compatible:array<string,mixed>}> $selectedLaneSet
     * @param list<TargetType>                                               $requiredTypes
     * @param array<string,list<Target>>                                     $targetsByType
     * @param array<string,int>                                              $targetSupplyByType
     * @param array<string,int>                                              $remainingByTypeKey
     */
    private function buildAssignments(
        array $selectedLaneSet,
        array $requiredTypes,
        array $targetsByType,
        array $targetSupplyByType,
        array $remainingByTypeKey,
        int $perRoundCapacity,
    ): TournamentTargetCollection {
        $shootingLanes         = [];
        $compatibleTypesByLane = [];
        foreach ($selectedLaneSet as $idx => $laneData) {
            $shootingLanes[$idx]         = $laneData['lane'];
            $compatibleTypesByLane[$idx] = $laneData['compatible'];
        }

        $typeCursor        = 0;
        $laneTargetBinding = [];
        $usedTargetIds     = [];
        $bindingsPerType   = [];
        $assignments       = new TournamentTargetCollection();
        $remainingTotal    = array_sum($remainingByTypeKey);
        $round             = 1;

        while ($remainingTotal > 0) {
            for ($slot = 0; $slot < $perRoundCapacity && $remainingTotal > 0; $slot++) {
                [$selectedType, $selectedRangeData, $typeCursor] = $this->selectTypeForSlot(
                    $requiredTypes,
                    $typeCursor,
                    $remainingByTypeKey,
                    $compatibleTypesByLane[$slot],
                    $bindingsPerType,
                    $targetSupplyByType,
                );

                $typeKey = $selectedType->value;

                if (! isset($laneTargetBinding[$slot][$typeKey])) {
                    $laneTargetBinding[$slot][$typeKey] = $this->bindTargetToLane(
                        $shootingLanes[$slot],
                        $targetsByType[$typeKey],
                        $usedTargetIds,
                        $selectedRangeData,
                    );

                    $bindingsPerType[$typeKey] = ($bindingsPerType[$typeKey] ?? 0) + 1;
                }

                $binding = $laneTargetBinding[$slot][$typeKey];
                $stakes  = $this->generateStakeDistances(
                    $binding['stakeRanges'],
                    $binding['laneMaxDistance'],
                );

                $assignments->add(
                    new TournamentTarget(
                        round: $round,
                        shootingLane: $binding['shootingLane'],
                        target: $binding['target'],
                        distance: $stakes->get($binding['farthestStake']),
                        stakes: $stakes,
                    ),
                );

                $remainingByTypeKey[$typeKey]--;
                $remainingTotal--;
            }

            $round++;
        }

        return $assignments;
    }

    /**
     * @param list<TargetType>                                                                                $requiredTypes
     * @param array<string,int>                                                                               $remainingByTypeKey
     * @param array<string,array{stakeRanges:array<string,array{min:float,max:float}>, farthestStake:string}> $compatiblesForLane
     * @param array<string,int>                                                                               $bindingsPerType
     * @param array<string,int>                                                                               $targetSupplyByType
     *
     * @return array{0:TargetType,1:array{stakeRanges:array<string,array{min:float,max:float}>, farthestStake:string},2:int}
     */
    private function selectTypeForSlot(
        array $requiredTypes,
        int $typeCursor,
        array $remainingByTypeKey,
        array $compatiblesForLane,
        array $bindingsPerType,
        array $targetSupplyByType,
    ): array {
        $typeCount = count($requiredTypes);
        for ($offset = 0; $offset < $typeCount; $offset++) {
            $candidateIndex = ($typeCursor + $offset) % $typeCount;
            $candidateType  = $requiredTypes[$candidateIndex];
            $candidateKey   = $candidateType->value;

            if (($remainingByTypeKey[$candidateKey] ?? 0) <= 0) {
                continue;
            }

            if (! isset($compatiblesForLane[$candidateKey])) {
                continue;
            }

            $bindingExists = isset($bindingsPerType[$candidateKey]);
            if (! $bindingExists && (($bindingsPerType[$candidateKey] ?? 0) >= ($targetSupplyByType[$candidateKey] ?? 0))) {
                continue;
            }

            $typeCursor = ($candidateIndex + 1) % $typeCount;

            return [$candidateType, $compatiblesForLane[$candidateKey], $typeCursor];
        }

        throw new RuntimeException('Unable to place a required target type on lane.');
    }

    /**
     * @param list<Target>                                                                      $targetPool
     * @param array{stakeRanges:array<string,array{min:float,max:float}>, farthestStake:string} $selectedRangeData
     * @param array<int,bool>                                                                   $usedTargetIds
     *
     * @return array{shootingLane:ShootingLane,target:Target,stakeRanges:array<string,array{min:float,max:float}>,farthestStake:string,laneMaxDistance:float}
     */
    private function bindTargetToLane(
        ShootingLane $lane,
        array $targetPool,
        array &$usedTargetIds,
        array $selectedRangeData,
    ): array {
        $bound = null;
        foreach ($targetPool as $candidateTarget) {
            $candidateId = spl_object_id($candidateTarget);
            if (! isset($usedTargetIds[$candidateId])) {
                $bound                       = $candidateTarget;
                $usedTargetIds[$candidateId] = true;
                break;
            }
        }

        if ($bound === null) {
            throw new RuntimeException(sprintf('No available target of type for lane %s.', $lane->name()));
        }

        return [
            'shootingLane' => $lane,
            'target' => $bound,
            'stakeRanges' => $selectedRangeData['stakeRanges'],
            'farthestStake' => $selectedRangeData['farthestStake'],
            'laneMaxDistance' => $lane->maxDistance(),
        ];
    }

    /** @param array<string,array{min:float,max:float}> $stakeRanges */
    private function generateStakeDistances(array $stakeRanges, float $laneMaxDistance): StakeDistances
    {
        $stakes = [];
        foreach ($stakeRanges as $stake => $range) {
            $maxAllowedDistance = max($range['min'], min($laneMaxDistance, $range['max']));
            $minInt             = (int) ceil($range['min']);
            $maxInt             = (int) floor($maxAllowedDistance);
            $base               = $minInt >= $maxInt ? $minInt : random_int($minInt, $maxInt);
            $step               = random_int(0, 1) === 1 ? 5 : 1;
            $direction          = random_int(0, 1) === 1 ? 1 : -1;
            $adjusted           = $base + ($step * $direction);
            $stakes[$stake]     = max($minInt, min($maxInt, $adjusted));
        }

        return new StakeDistances($stakes);
    }
}
