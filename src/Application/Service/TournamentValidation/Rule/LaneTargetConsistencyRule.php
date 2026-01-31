<?php

declare(strict_types=1);

namespace App\Application\Service\TournamentValidation\Rule;

use App\Application\Service\TournamentValidation\TournamentValidationIssue;
use App\Domain\Entity\Tournament;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

use function array_keys;
use function array_unique;
use function count;
use function implode;
use function sort;

#[AsTaggedItem(priority: 145)]
final class LaneTargetConsistencyRule implements TournamentValidationRule
{
    /** @return list<TournamentValidationIssue> */
    public function validate(Tournament $tournament): array
    {
        $issues      = [];
        $assignments = [];

        foreach ($tournament->targets() as $assignment) {
            $lane   = $assignment->shootingLane();
            $target = $assignment->target();
            $round  = $assignment->round();
            $laneId = $lane->id();

            if (! isset($assignments[$laneId])) {
                $assignments[$laneId] = [
                    'laneName' => $lane->name(),
                    'targets' => [],
                    'targetNames' => [],
                ];
            }

            $assignments[$laneId]['targets'][$target->id()][]   = $round;
            $assignments[$laneId]['targetNames'][$target->id()] = $target->name();
        }

        foreach ($assignments as $laneId => $data) {
            $targetIds = array_keys($data['targets']);
            if (count($targetIds) <= 1) {
                continue;
            }

            $summaries = [];
            foreach ($data['targets'] as $targetId => $rounds) {
                $uniqueRounds = array_unique($rounds);
                sort($uniqueRounds);
                $summaries[] = 'rounds ' . implode(', ', $uniqueRounds) . ': "' . $data['targetNames'][$targetId] . '"';
            }

            $issues[] = new TournamentValidationIssue(
                rule: 'Lane Target Consistency',
                message: 'Lane "' . $data['laneName'] . '" must keep the same target across rounds. Found ' . implode('; ', $summaries) . '.',
                context: [
                    'laneId' => $laneId,
                    'targets' => $data['targetNames'],
                ],
            );
        }

        return $issues;
    }
}
