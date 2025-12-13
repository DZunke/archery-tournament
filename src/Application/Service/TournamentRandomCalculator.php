<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Application\Service\DTO\GeneratedTournament;
use App\Domain\Entity\ArcheryGround;
use App\Domain\ValueObject\Ruleset;

use App\Domain\ValueObject\TargetType;
use function ceil;
use function count;
use function shuffle;

final class TournamentRandomCalculator
{
    public function generate(ArcheryGround $archeryGround, Ruleset $ruleset, int $amountOfTargets): GeneratedTournament
    {
        $tournamentDto = new GeneratedTournament(
            archeryGround: $archeryGround,
            ruleset: $ruleset,
            amountOfTargets: $amountOfTargets,
        );

        $this->calculateNeededRounds($tournamentDto);
        $this->assignUsableLocationLanes($tournamentDto);

        return $tournamentDto;
    }

    /**
     * todo: the calculation currently not assuming that a round could be split into multiple rounds, eg. 8, 8, 8, and 3 targets
     */
    private function calculateNeededRounds(GeneratedTournament $tournamentDto): void
    {
        $availableLocationLaneAmount = $tournamentDto->archeryGround->numberOfShootingLanes();

        $requiredTargetTypes      = $tournamentDto->ruleset->requiredTargetTypes();
        $totalRequiredTargetTypes = count($requiredTargetTypes);

        do {
            // Get the needed amount of rounds with utilizing all available lanes
            $neededRounds = (int) ceil($tournamentDto->amountOfTargets / $availableLocationLaneAmount);
            if ($neededRounds === 1) {
                // When the location fits all targets in a single round, we only need this single round and must not check further
                $tournamentDto->neededRounds  = 1;
                $tournamentDto->lanesPerRound = [1 => $tournamentDto->amountOfTargets];

                break;
            }

            // Check if the required target types fit evenly into the available lanes per round, otherwise we need lesser lanes
            $lanesPerRound = (int) ceil($tournamentDto->amountOfTargets / $neededRounds);
            if ($lanesPerRound % $totalRequiredTargetTypes === 0) {
                $tournamentDto->neededRounds = $neededRounds;
                // Set the amount of lanes per round that should be used, eg. round 1 = 8 lanes, round 2 = 8 lanes, round 3 = 4 lanes
                for ($round = 1; $round <= $neededRounds; $round++) {
                    if ($round < $neededRounds) {
                        $tournamentDto->lanesPerRound[$round] = $availableLocationLaneAmount;
                    } else {
                        // Last round takes the remaining targets
                        $remainingTargets                     = $tournamentDto->amountOfTargets - ($availableLocationLaneAmount * ($neededRounds - 1));
                        $tournamentDto->lanesPerRound[$round] = $remainingTargets;
                    }
                }
            } else {
                // Reduce the available lanes and try again
                $availableLocationLaneAmount--;
            }
        } while ($tournamentDto->neededRounds === 0);
    }

    /**
     * The function will assign usable lanes to the tournament based on the minimum needed target type
     * assignment. Each target group has to be equally represented in the utilized lanes and if a lane
     * is utilized it can not be utilized again for another target type. So for each type or target group
     * we need to find lanes that can host the target type based on the maximum distance of the lane and
     * the distance requirements of the target type based on the ruleset.
     *
     * This should be a bit random that not only the first three lanes are for the same target type.
     */
    private function assignUsableLocationLanes(GeneratedTournament $tournamentDto): void
    {
        $requiredTargetTypes = $tournamentDto->ruleset->requiredTargetTypes();
        $amountOfEachTargetType = (int) ($tournamentDto->lanesPerRound[1] / count($requiredTargetTypes));
        $availableLanes = $tournamentDto->archeryGround->shootingLanes();

        // Order the required target types by their minimum distance descending to assign the most demanding target types first
        usort($requiredTargetTypes, function (TargetType $a, TargetType $b) use ($tournamentDto) {
            $aDistances = $tournamentDto->ruleset->getMaxStakeDistance($a);
            $bDistances = $tournamentDto->ruleset->getMaxStakeDistance($b);

            return $aDistances <=> $bDistances;
        });

        foreach ($requiredTargetTypes as $targetType) {
            // Shuffle the available lanes to get a bit of randomness in the assignment
            shuffle($availableLanes);

            $assignedLanes = 0;
            foreach ($availableLanes as $key => $lane) {
                $maxStakeDistance = $tournamentDto->ruleset->getMaxStakeDistance($targetType);
                if ($lane->maxDistance() <= $maxStakeDistance) {
                    // Lane can be utilized for this target type
                    $tournamentDto->utilizedLanes[] = [
                        'lane' => $lane,
                        'type' => $targetType,
                    ];
                    unset($availableLanes[$key]);
                    $assignedLanes++;

                    if ($assignedLanes >= $amountOfEachTargetType) {
                        // Assigned enough lanes for this target type
                        break;
                    }
                }
            }
        }

        dump($tournamentDto->utilizedLanes);
    }
}
