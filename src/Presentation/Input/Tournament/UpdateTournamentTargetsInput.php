<?php

declare(strict_types=1);

namespace App\Presentation\Input\Tournament;

use App\Application\Command\Tournament\TournamentTargetAssignment;
use App\Application\Command\Tournament\UpdateTournamentTargets;
use Symfony\Component\HttpFoundation\Request;

use function is_array;

final readonly class UpdateTournamentTargetsInput
{
    /** @param array<array-key,mixed> $rows */
    public function __construct(private array $rows)
    {
    }

    public static function fromRequest(Request $request): self
    {
        /** @var array<array-key,mixed> $rows */
        $rows = $request->request->all('assignments');

        return new self($rows);
    }

    /** @return list<string> */
    public function errors(): array
    {
        $errors = [];

        foreach ($this->rows as $row) {
            if (! is_array($row)) {
                $errors[] = 'Invalid assignment payload.';
                continue;
            }

            if (! isset($row['round'], $row['shooting_lane_id'], $row['target_id'], $row['stakes'])) {
                $errors[] = 'Each assignment must include round, lane, target, and stakes.';
                continue;
            }

            if (is_array($row['stakes'])) {
                continue;
            }

            $errors[] = 'Stake distances must be provided.';
        }

        return $errors;
    }

    public function toCommand(string $tournamentId): UpdateTournamentTargets
    {
        $assignments = [];

        foreach ($this->rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $round  = isset($row['round']) ? (int) $row['round'] : 0;
            $lane   = isset($row['shooting_lane_id']) ? (string) $row['shooting_lane_id'] : '';
            $target = isset($row['target_id']) ? (string) $row['target_id'] : '';
            $stakes = [];

            if (isset($row['stakes']) && is_array($row['stakes'])) {
                foreach ($row['stakes'] as $stake => $value) {
                    $stakes[(string) $stake] = (int) $value;
                }
            }

            if ($lane === '') {
                continue;
            }

            if ($target === '') {
                continue;
            }

            if ($round <= 0) {
                continue;
            }

            if ($stakes === []) {
                continue;
            }

            $assignments[] = new TournamentTargetAssignment(
                $round,
                $lane,
                $target,
                $stakes,
            );
        }

        return new UpdateTournamentTargets($tournamentId, $assignments);
    }
}
