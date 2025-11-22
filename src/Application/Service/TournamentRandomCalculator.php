<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Domain\Entity\ArcheryGround\ShootingLane;
use App\Domain\Entity\ArcheryGround\Target;
use App\Domain\Entity\Tournament;
use Webmozart\Assert\Assert;

use function array_filter;
use function array_rand;
use function array_values;
use function ceil;
use function in_array;
use function min;

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

        $shootingLanes        = $archeryGround->shootingLanes();
        $amountOfRoundsNeeded = (int) ceil($tournament->numberOfTargets() / $targetsPerRound);

        $assignments      = [];
        $remainingTargets = $tournament->numberOfTargets();
        for ($round = 1; $round <= $amountOfRoundsNeeded; $round++) {
            $targetsThisRound = min($targetsPerRound, $remainingTargets);

            for ($slot = 0; $slot < $targetsThisRound; $slot++) {
                $assignments[] = [
                    'round' => $round,
                    'shootingLane' => $shootingLanes[$slot],
                    'target' => $availableTargets[array_rand($availableTargets)],
                ];
            }

            $remainingTargets -= $targetsThisRound;
        }

        return $assignments;
    }
}
