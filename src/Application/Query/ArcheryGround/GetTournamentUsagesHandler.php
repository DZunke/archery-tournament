<?php

declare(strict_types=1);

namespace App\Application\Query\ArcheryGround;

use Doctrine\DBAL\Connection;

final readonly class GetTournamentUsagesHandler
{
    public function __construct(private Connection $connection)
    {
    }

    public function __invoke(GetTournamentUsages $query): TournamentUsages
    {
        $laneUsages   = $this->fetchLaneUsages($query->archeryGroundId);
        $targetUsages = $this->fetchTargetUsages($query->archeryGroundId);

        return new TournamentUsages($laneUsages, $targetUsages);
    }

    /** @return array<string, list<array{id: string, name: string}>> */
    private function fetchLaneUsages(string $archeryGroundId): array
    {
        $sql = <<<'SQL'
            SELECT DISTINCT
                tt.shooting_lane_id,
                t.id AS tournament_id,
                t.name AS tournament_name
            FROM tournament_targets tt
            INNER JOIN tournaments t ON tt.tournament_id = t.id
            INNER JOIN shooting_lanes sl ON tt.shooting_lane_id = sl.id
            WHERE sl.archery_ground_id = ?
            ORDER BY t.name
            SQL;

        $rows   = $this->connection->fetchAllAssociative($sql, [$archeryGroundId]);
        $usages = [];

        foreach ($rows as $row) {
            $laneId = (string) $row['shooting_lane_id'];
            if (! isset($usages[$laneId])) {
                $usages[$laneId] = [];
            }

            $usages[$laneId][] = [
                'id' => (string) $row['tournament_id'],
                'name' => (string) $row['tournament_name'],
            ];
        }

        return $usages;
    }

    /** @return array<string, list<array{id: string, name: string}>> */
    private function fetchTargetUsages(string $archeryGroundId): array
    {
        $sql = <<<'SQL'
            SELECT DISTINCT
                tt.target_id,
                t.id AS tournament_id,
                t.name AS tournament_name
            FROM tournament_targets tt
            INNER JOIN tournaments t ON tt.tournament_id = t.id
            INNER JOIN targets tg ON tt.target_id = tg.id
            WHERE tg.archery_ground_id = ?
            ORDER BY t.name
            SQL;

        $rows   = $this->connection->fetchAllAssociative($sql, [$archeryGroundId]);
        $usages = [];

        foreach ($rows as $row) {
            $targetId = (string) $row['target_id'];
            if (! isset($usages[$targetId])) {
                $usages[$targetId] = [];
            }

            $usages[$targetId][] = [
                'id' => (string) $row['tournament_id'],
                'name' => (string) $row['tournament_name'],
            ];
        }

        return $usages;
    }
}
