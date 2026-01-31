<?php

declare(strict_types=1);

namespace App\Application\Service\TournamentValidation\Rule;

use App\Application\Service\TournamentValidation\TournamentValidationIssue;
use App\Domain\Entity\Tournament;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

use function array_unique;
use function array_values;
use function count;
use function implode;

#[AsTaggedItem(priority: 140)]
final class LaneUniquenessRule implements TournamentValidationRule
{
    /** @return list<TournamentValidationIssue> */
    public function validate(Tournament $tournament): array
    {
        $issues      = [];
        $assignments = [];

        foreach ($tournament->targets() as $assignment) {
            $round  = $assignment->round();
            $lane   = $assignment->shootingLane();
            $target = $assignment->target();

            if (! isset($assignments[$round][$lane->id()])) {
                $assignments[$round][$lane->id()] = [
                    'laneName' => $lane->name(),
                    'targets' => [],
                ];
            }

            $assignments[$round][$lane->id()]['targets'][] = $target->name();
        }

        foreach ($assignments as $round => $lanes) {
            foreach ($lanes as $laneId => $data) {
                $targets = array_values(array_unique($data['targets']));
                if (count($targets) <= 1) {
                    continue;
                }

                $issues[] = new TournamentValidationIssue(
                    rule: 'Lane Uniqueness',
                    message: 'Lane "' . $data['laneName'] . '" is assigned multiple times in round ' . $round . ' (' . implode(', ', $targets) . ').',
                    context: [
                        'round' => $round,
                        'laneId' => $laneId,
                        'targets' => $targets,
                    ],
                );
            }
        }

        return $issues;
    }
}
