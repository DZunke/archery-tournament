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
use function ceil;
use function count;
use function in_array;
use function min;
use function shuffle;

final class TournamentRandomCalculator
{
    /** @return list<array{round:int, shootingLane:ShootingLane, target:Target}> */
    public function calculate(Tournament $tournament): array
    {
        $archeryGround = $tournament->archeryGround();
        $ruleset       = $tournament->ruleset();

        $targetsPerRound = $archeryGround->numberOfShootingLanes();
        Assert::greaterThan($targetsPerRound, 0, 'The archery ground must have at least one shooting lane.');

        $availableTargets = array_values(array_filter(
            $archeryGround->targetStorage(),
            static fn (Target $target): bool => in_array($target->type(), $ruleset->allowedTargetTypes(), true),
        ));
        Assert::notEmpty($availableTargets, 'The archery ground must have targets fitting the ruleset.');

        $perRoundCapacity = min($targetsPerRound, count($availableTargets), $tournament->numberOfTargets());
        Assert::greaterThan($perRoundCapacity, 0, 'The tournament must allow at least one target per round.');

        $shootingLanes = array_slice($archeryGround->shootingLanes(), 0, $perRoundCapacity);
        shuffle($availableTargets);
        $laneTargets = array_slice($availableTargets, 0, $perRoundCapacity);

        $amountOfRoundsNeeded = (int) ceil($tournament->numberOfTargets() / $perRoundCapacity);

        $assignments      = [];
        $remainingTargets = $tournament->numberOfTargets();
        for ($round = 1; $round <= $amountOfRoundsNeeded; $round++) {
            $targetsThisRound = min($perRoundCapacity, $remainingTargets);

            for ($slot = 0; $slot < $targetsThisRound; $slot++) {
                $assignments[] = [
                    'round' => $round,
                    'shootingLane' => $shootingLanes[$slot],
                    'target' => $laneTargets[$slot],
                ];
            }

            $remainingTargets -= $targetsThisRound;
        }

        return $assignments;
    }
}
