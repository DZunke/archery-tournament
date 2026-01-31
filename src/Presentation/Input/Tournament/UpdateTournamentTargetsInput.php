<?php

declare(strict_types=1);

namespace App\Presentation\Input\Tournament;

use App\Application\Command\Tournament\TournamentTargetAssignment;
use App\Application\Command\Tournament\UpdateTournamentTargets;
use App\Application\Service\TournamentValidation\TournamentValidationIssue;
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

        foreach ($this->issues() as $issue) {
            $row = $issue->context['row'] ?? null;
            if ($row !== null) {
                $errors[] = 'Row ' . $row . ': ' . $issue->message;
            } else {
                $errors[] = $issue->message;
            }
        }

        return $errors;
    }

    /** @return list<TournamentValidationIssue> */
    public function issues(): array
    {
        $issues = [];

        foreach ($this->rows as $index => $row) {
            $rowNumber = $index + 1;

            if (! is_array($row)) {
                $issues[] = new TournamentValidationIssue(
                    rule: 'Input',
                    message: 'Invalid assignment payload.',
                    context: ['row' => $rowNumber],
                );
                continue;
            }

            if (! isset($row['round'], $row['shooting_lane_id'], $row['target_id'], $row['stakes'])) {
                $issues[] = new TournamentValidationIssue(
                    rule: 'Input',
                    message: 'Each assignment must include round, lane, target, and stakes.',
                    context: ['row' => $rowNumber],
                );
                continue;
            }

            if (is_array($row['stakes'])) {
                continue;
            }

            $issues[] = new TournamentValidationIssue(
                rule: 'Input',
                message: 'Stake distances must be provided.',
                context: ['row' => $rowNumber],
            );
        }

        return $issues;
    }

    /** @return list<array{rowIndex: int, round: int, shootingLaneId: string, targetId: string, stakes: array<string,int>}> */
    public function rows(): array
    {
        $rows = [];

        foreach ($this->rows as $index => $row) {
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

            $rows[] = [
                'rowIndex' => (int) $index,
                'round' => $round,
                'shootingLaneId' => $lane,
                'targetId' => $target,
                'stakes' => $stakes,
            ];
        }

        return $rows;
    }

    public function toCommand(string $tournamentId): UpdateTournamentTargets
    {
        $assignments = [];

        foreach ($this->rows as $index => $row) {
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

            $assignments[] = new TournamentTargetAssignment(
                (int) $index,
                $round,
                $lane,
                $target,
                $stakes,
            );
        }

        return new UpdateTournamentTargets($tournamentId, $assignments);
    }
}
