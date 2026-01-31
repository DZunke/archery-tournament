<?php

declare(strict_types=1);

namespace App\Presentation\View;

use App\Domain\Entity\Tournament;
use App\Domain\Entity\TournamentTarget;

use function iterator_to_array;
use function strnatcasecmp;
use function usort;

final class TournamentAssignmentViewBuilder
{
    /** @return list<TournamentTarget> */
    public function sortTargets(Tournament $tournament): array
    {
        $targets = iterator_to_array($tournament->targets(), false);

        usort(
            $targets,
            static function (TournamentTarget $left, TournamentTarget $right): int {
                $roundComparison = $left->round() <=> $right->round();
                if ($roundComparison !== 0) {
                    return $roundComparison;
                }

                $laneComparison = strnatcasecmp($left->shootingLane()->name(), $right->shootingLane()->name());
                if ($laneComparison !== 0) {
                    return $laneComparison;
                }

                return strnatcasecmp($left->target()->name(), $right->target()->name());
            },
        );

        return $targets;
    }

    /**
     * @param list<TournamentTarget>|null $sortedTargets
     *
     * @return list<array{
     *     round: int,
     *     laneId: string,
     *     laneName: string,
     *     targetName: string,
     *     targetType: string,
     *     targetImage: string,
     *     stakes: array<string,int>,
     *     diffs: array<string,int>,
     *     diffRound: int|null,
     * }>
     */
    public function buildCards(Tournament $tournament, array|null $sortedTargets = null): array
    {
        $targets    = $sortedTargets ?? $this->sortTargets($tournament);
        $cards      = [];
        $lastByLane = [];
        foreach ($targets as $assignment) {
            $lane    = $assignment->shootingLane();
            $target  = $assignment->target();
            $stakes  = $assignment->stakes()->all();
            $diffs   = [];
            $diffRef = null;

            $previous = $lastByLane[$lane->id()] ?? null;
            if ($previous !== null && $previous['round'] !== $assignment->round()) {
                foreach ($stakes as $stake => $distance) {
                    if (! isset($previous['stakes'][$stake])) {
                        continue;
                    }

                    $delta = $distance - $previous['stakes'][$stake];
                    if ($delta === 0) {
                        continue;
                    }

                    $diffs[$stake] = $delta;
                }

                if ($diffs !== []) {
                    $diffRef = $previous['round'];
                }
            }

            $cards[] = [
                'round' => $assignment->round(),
                'laneId' => $lane->id(),
                'laneName' => $lane->name(),
                'targetName' => $target->name(),
                'targetType' => $target->type()->value,
                'targetImage' => $target->image(),
                'stakes' => $stakes,
                'diffs' => $diffs,
                'diffRound' => $diffRef,
            ];

            $lastByLane[$lane->id()] = [
                'round' => $assignment->round(),
                'stakes' => $stakes,
            ];
        }

        return $cards;
    }
}
