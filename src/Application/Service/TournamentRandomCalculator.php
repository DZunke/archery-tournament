<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Domain\Entity\ArcheryGround\ShootingLane;
use App\Domain\Entity\ArcheryGround\Target;
use App\Domain\Entity\Tournament;
use Webmozart\Assert\Assert;

use function array_filter;
use function array_values;
use function ceil;
use function count;
use function in_array;
use function lcg_value;
use function min;
use function shuffle;

final class TournamentRandomCalculator
{
    /** @return list<array{round:int, shootingLane:ShootingLane, target:Target, distance:float}> */
    public function calculate(Tournament $tournament): array
    {
        $archeryGround = $tournament->archeryGround();
        $ruleset       = $tournament->ruleset();
        $shootingLanes = $archeryGround->shootingLanes();
        Assert::notEmpty($shootingLanes, 'The archery ground must have at least one shooting lane.');

        $availableTargets = array_values(array_filter(
            $archeryGround->targetStorage(),
            static fn (Target $target): bool => in_array($target->type(), $ruleset->allowedTargetTypes(), true),
        ));
        Assert::notEmpty($availableTargets, 'The archery ground must have targets fitting the ruleset.');

        shuffle($availableTargets);
        $laneTargets = [];
        foreach ($shootingLanes as $shootingLane) {
            $compatibleIndex = null;
            $distanceRange   = null;

            foreach ($availableTargets as $index => $target) {
                $range = $ruleset->distanceRange($target->type());
                if ($range['min'] > $shootingLane->maxDistance()) {
                    continue;
                }

                $compatibleIndex = $index;
                $distanceRange   = $range;
                break;
            }

            if ($compatibleIndex === null) {
                continue;
            }

            $target = $availableTargets[$compatibleIndex];
            unset($availableTargets[$compatibleIndex]);
            $availableTargets = array_values($availableTargets);

            $maxAllowedDistance = min($shootingLane->maxDistance(), $distanceRange['max']);
            $distance           = $maxAllowedDistance === $distanceRange['min']
                ? $maxAllowedDistance
                : $distanceRange['min'] + (lcg_value() * ($maxAllowedDistance - $distanceRange['min']));

            $laneTargets[] = [
                'shootingLane' => $shootingLane,
                'target' => $target,
                'distance' => $distance,
            ];

            if (count($laneTargets) >= $tournament->numberOfTargets()) {
                break;
            }
        }

        $perRoundCapacity = min(count($laneTargets), $tournament->numberOfTargets());
        Assert::greaterThan($perRoundCapacity, 0, 'The tournament must allow at least one target per round.');

        $amountOfRoundsNeeded = (int) ceil($tournament->numberOfTargets() / $perRoundCapacity);

        $assignments      = [];
        $remainingTargets = $tournament->numberOfTargets();
        for ($round = 1; $round <= $amountOfRoundsNeeded; $round++) {
            $targetsThisRound = min($perRoundCapacity, $remainingTargets);

            for ($slot = 0; $slot < $targetsThisRound; $slot++) {
                $laneTarget = $laneTargets[$slot];
                $assignments[] = [
                    'round' => $round,
                    'shootingLane' => $laneTarget['shootingLane'],
                    'target' => $laneTarget['target'],
                    'distance' => $laneTarget['distance'],
                ];
            }

            $remainingTargets -= $targetsThisRound;
        }

        return $assignments;
    }
}
