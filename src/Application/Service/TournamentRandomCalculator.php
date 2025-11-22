<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Domain\Entity\ArcheryGround\ShootingLane;
use App\Domain\Entity\ArcheryGround\Target;
use App\Domain\Entity\Tournament;
use Webmozart\Assert\Assert;

use function array_filter;
use function array_slice;
use function array_values;
use function count;
use function in_array;
use function lcg_value;
use function min;
use function shuffle;
use function spl_object_id;
use function usort;

final class TournamentRandomCalculator
{
    /** @return list<array{round:int, shootingLane:ShootingLane, target:Target, distance:float}> */
    public function calculate(Tournament $tournament): array
    {
        $archeryGround = $tournament->archeryGround();
        $ruleset       = $tournament->ruleset();
        $shootingLanes = $archeryGround->shootingLanes();
        Assert::notEmpty($shootingLanes, 'The archery ground must have at least one shooting lane.');

        $requiredTypes = $ruleset->requiredTargetTypes();
        Assert::greaterThanEq(
            $tournament->numberOfTargets(),
            count($requiredTypes),
            'The tournament must request at least one target per required target group.',
        );

        $availableTargets = array_values(array_filter(
            $archeryGround->targetStorage(),
            static fn (Target $target): bool => in_array($target->type(), $requiredTypes, true),
        ));
        Assert::notEmpty($availableTargets, 'The archery ground must have targets fitting the ruleset.');

        shuffle($availableTargets);
        $targetsByType = [];
        foreach ($availableTargets as $target) {
            $targetsByType[$target->type()->value][] = $target;
        }
        $targetSupplyByType = [];
        foreach ($targetsByType as $typeKey => $targets) {
            $targetSupplyByType[$typeKey] = count($targets);
        }

        foreach ($requiredTypes as $requiredType) {
            Assert::keyExists(
                $targetsByType,
                $requiredType->value,
                sprintf('At least one target of type %s is required.', $requiredType->value),
            );
        }

        $compatibleTypesByLane = [];
        $compatibleLaneData   = [];
        foreach ($shootingLanes as $index => $lane) {
            $compatibleTypesByLane[$index] = [];
            foreach ($requiredTypes as $requiredType) {
                $range = $ruleset->distanceRange($requiredType);
                if ($range['min'] <= $lane->maxDistance()) {
                    $compatibleTypesByLane[$index][$requiredType->value] = $range;
                }
            }

            Assert::notEmpty(
                $compatibleTypesByLane[$index],
                sprintf('Shooting lane %s is incompatible with all required target types.', $lane->name()),
            );

            $compatibleLaneData[] = [
                'lane' => $lane,
                'compatible' => $compatibleTypesByLane[$index],
            ];
        }

        Assert::notEmpty($compatibleLaneData, 'No compatible shooting lanes available for required target types.');

        usort(
            $compatibleLaneData,
            static function (array $a, array $b): int {
                $countA = count($a['compatible']);
                $countB = count($b['compatible']);

                if ($countA === $countB) {
                    return $b['lane']->maxDistance() <=> $a['lane']->maxDistance();
                }

                return $countB <=> $countA;
            },
        );

        $maxUsableLanes  = count($compatibleLaneData) > 1 ? count($compatibleLaneData) - 1 : 1;
        $perRoundCapacity = min(
            $tournament->numberOfTargets(),
            $maxUsableLanes,
            count($compatibleLaneData),
            count($availableTargets),
        );
        Assert::greaterThan($perRoundCapacity, 0, 'The tournament must allow at least one target per round.');

        $selectedLanes = array_slice($compatibleLaneData, 0, $perRoundCapacity);
        $shootingLanes = [];
        $compatibleTypesByLane = [];
        foreach ($selectedLanes as $idx => $laneData) {
            $shootingLanes[$idx] = $laneData['lane'];
            $compatibleTypesByLane[$idx] = $laneData['compatible'];
        }

        $typeCount          = count($requiredTypes);
        $basePerType        = intdiv($tournament->numberOfTargets(), $typeCount);
        $withExtra          = $tournament->numberOfTargets() % $typeCount;
        $remainingByTypeKey = [];
        foreach ($requiredTypes as $index => $requiredType) {
            $remainingByTypeKey[$requiredType->value] = $basePerType + ($index < $withExtra ? 1 : 0);
        }

        $typeCursor        = 0;
        $laneTargetBinding = [];
        $usedTargetIds     = [];
        $bindingsPerType   = [];
        $assignments       = [];
        $remainingTotal    = $tournament->numberOfTargets();
        $round             = 1;

        while ($remainingTotal > 0) {
            for ($slot = 0; $slot < $perRoundCapacity && $remainingTotal > 0; $slot++) {
                $selectedType  = null;
                $selectedRange = null;

                for ($offset = 0; $offset < $typeCount; $offset++) {
                    $candidateIndex = ($typeCursor + $offset) % $typeCount;
                    $candidateType  = $requiredTypes[$candidateIndex];
                    $candidateKey   = $candidateType->value;

                    if (($remainingByTypeKey[$candidateKey] ?? 0) <= 0) {
                        continue;
                    }

                    if (! isset($compatibleTypesByLane[$slot][$candidateKey])) {
                        continue;
                    }

                    $bindingExists = isset($laneTargetBinding[$slot][$candidateKey]);
                    if (! $bindingExists && (($bindingsPerType[$candidateKey] ?? 0) >= $targetSupplyByType[$candidateKey])) {
                        continue;
                    }

                    $selectedType  = $candidateType;
                    $selectedRange = $compatibleTypesByLane[$slot][$candidateKey];
                    $typeCursor    = ($candidateIndex + 1) % $typeCount;
                    break;
                }

                Assert::notNull(
                    $selectedType,
                    sprintf('Unable to place a required target type on lane %s.', $shootingLanes[$slot]->name()),
                );

                $typeKey = $selectedType->value;

                if (! isset($laneTargetBinding[$slot][$typeKey])) {
                    $targetPool = $targetsByType[$typeKey];
                    $bound      = null;
                    foreach ($targetPool as $candidateTarget) {
                        $candidateId = spl_object_id($candidateTarget);
                        if (! isset($usedTargetIds[$candidateId])) {
                            $bound                    = $candidateTarget;
                            $usedTargetIds[$candidateId] = true;
                            break;
                        }
                    }

                    Assert::notNull(
                        $bound,
                        sprintf('No available target of type %s for lane %s.', $typeKey, $shootingLanes[$slot]->name()),
                    );

                    $maxAllowedDistance = min($shootingLanes[$slot]->maxDistance(), $selectedRange['max']);
                    $distance           = $maxAllowedDistance === $selectedRange['min']
                        ? $maxAllowedDistance
                        : $selectedRange['min'] + (lcg_value() * ($maxAllowedDistance - $selectedRange['min']));

                    $laneTargetBinding[$slot][$typeKey] = [
                        'shootingLane' => $shootingLanes[$slot],
                        'target' => $bound,
                        'distance' => $distance,
                    ];

                    $bindingsPerType[$typeKey] = ($bindingsPerType[$typeKey] ?? 0) + 1;
                }

                $binding = $laneTargetBinding[$slot][$typeKey];

                $assignments[] = [
                    'round' => $round,
                    'shootingLane' => $binding['shootingLane'],
                    'target' => $binding['target'],
                    'distance' => $binding['distance'],
                ];

                $remainingByTypeKey[$typeKey]--;
                $remainingTotal--;
            }

            $round++;
        }

        return $assignments;
    }
}
