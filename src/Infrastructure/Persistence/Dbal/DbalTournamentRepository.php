<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Dbal;

use App\Domain\Entity\ArcheryGround;
use App\Domain\Entity\Tournament;
use App\Domain\Entity\TournamentTargetCollection;
use App\Domain\Repository\ArcheryGroundRepository;
use App\Domain\Repository\TournamentRepository;
use App\Infrastructure\Persistence\Dbal\Hydrator\TournamentHydrator;
use App\Infrastructure\Persistence\Dbal\Hydrator\TournamentTargetHydrator;
use Doctrine\DBAL\Connection;
use JsonException;
use Symfony\Component\Uid\Uuid;

use function json_decode;
use function json_encode;

use const JSON_THROW_ON_ERROR;

final readonly class DbalTournamentRepository implements TournamentRepository
{
    public function __construct(
        private Connection $connection,
        private ArcheryGroundRepository $archeryGroundRepository,
        private TournamentHydrator $tournamentHydrator,
        private TournamentTargetHydrator $tournamentTargetHydrator,
    ) {
    }

    public function nextIdentity(): string
    {
        return Uuid::v4()->toRfc4122();
    }

    public function save(Tournament $tournament): void
    {
        $exists = $this->connection->fetchOne(
            'SELECT 1 FROM tournaments WHERE id = ?',
            [$tournament->id()],
        );

        $payload = [
            'id' => $tournament->id(),
            'archery_ground_id' => $tournament->archeryGround()->id(),
            'name' => $tournament->name(),
            'event_date' => $tournament->eventDate()->format('Y-m-d'),
            'ruleset' => $tournament->ruleset()->value,
            'number_of_targets' => $tournament->numberOfTargets(),
        ];

        if ($exists !== false) {
            $this->connection->executeStatement(
                'UPDATE tournaments SET archery_ground_id = ?, name = ?, event_date = ?, ruleset = ?, number_of_targets = ? WHERE id = ?',
                [
                    $payload['archery_ground_id'],
                    $payload['name'],
                    $payload['event_date'],
                    $payload['ruleset'],
                    $payload['number_of_targets'],
                    $payload['id'],
                ],
            );
        } else {
            $this->connection->insert('tournaments', $payload);
        }

        $this->replaceTargets($tournament->id(), $tournament->targets());
    }

    public function find(string $id): Tournament|null
    {
        $row = $this->connection->fetchAssociative(
            'SELECT id, archery_ground_id, name, event_date, ruleset, number_of_targets FROM tournaments WHERE id = ?',
            [$id],
        );

        if ($row === false) {
            return null;
        }

        $archeryGround = $this->archeryGroundRepository->find((string) $row['archery_ground_id']);
        if (! $archeryGround instanceof ArcheryGround) {
            return null;
        }

        $targets = $this->loadTargets((string) $row['id'], $archeryGround);

        return $this->tournamentHydrator->hydrate(
            [
                'id' => (string) $row['id'],
                'name' => (string) $row['name'],
                'event_date' => (string) $row['event_date'],
                'ruleset' => (string) $row['ruleset'],
                'number_of_targets' => $row['number_of_targets'],
            ],
            $archeryGround,
            $targets,
        );
    }

    /** @return list<Tournament> */
    public function findAll(): array
    {
        $rows        = $this->connection->fetchAllAssociative('SELECT id FROM tournaments ORDER BY event_date DESC');
        $tournaments = [];

        foreach ($rows as $row) {
            $tournament = $this->find((string) $row['id']);
            if (! $tournament instanceof Tournament) {
                continue;
            }

            $tournaments[] = $tournament;
        }

        return $tournaments;
    }

    /** @return list<Tournament> */
    public function findByArcheryGround(string $archeryGroundId): array
    {
        $rows        = $this->connection->fetchAllAssociative(
            'SELECT id FROM tournaments WHERE archery_ground_id = ? ORDER BY event_date DESC',
            [$archeryGroundId],
        );
        $tournaments = [];

        foreach ($rows as $row) {
            $tournament = $this->find((string) $row['id']);
            if (! $tournament instanceof Tournament) {
                continue;
            }

            $tournaments[] = $tournament;
        }

        return $tournaments;
    }

    public function delete(string $id): void
    {
        $this->connection->executeStatement('DELETE FROM tournament_targets WHERE tournament_id = ?', [$id]);
        $this->connection->executeStatement('DELETE FROM tournaments WHERE id = ?', [$id]);
    }

    public function replaceTargets(string $tournamentId, TournamentTargetCollection $targets): void
    {
        $this->connection->executeStatement('DELETE FROM tournament_targets WHERE tournament_id = ?', [$tournamentId]);

        foreach ($targets as $target) {
            $this->connection->insert('tournament_targets', [
                'tournament_id' => $tournamentId,
                'round' => $target->round(),
                'shooting_lane_id' => $target->shootingLane()->id(),
                'target_id' => $target->target()->id(),
                'distance' => $target->distance(),
                'stakes' => $this->encodeStakes($target->stakes()->all()),
            ]);
        }
    }

    private function loadTargets(string $tournamentId, ArcheryGround $archeryGround): TournamentTargetCollection
    {
        $rows = $this->connection->fetchAllAssociative(
            'SELECT round, shooting_lane_id, target_id, distance, stakes FROM tournament_targets WHERE tournament_id = ? ORDER BY round, shooting_lane_id',
            [$tournamentId],
        );

        $lanesById = [];
        foreach ($archeryGround->shootingLanes() as $lane) {
            $lanesById[$lane->id()] = $lane;
        }

        $targetsById = [];
        foreach ($archeryGround->targetStorage() as $target) {
            $targetsById[$target->id()] = $target;
        }

        $collection = new TournamentTargetCollection();
        foreach ($rows as $row) {
            $lane   = $lanesById[(string) $row['shooting_lane_id']] ?? null;
            $target = $targetsById[(string) $row['target_id']] ?? null;
            if ($lane === null) {
                continue;
            }

            if ($target === null) {
                continue;
            }

            $stakes = $this->decodeStakes((string) $row['stakes']);
            if ($stakes === []) {
                continue;
            }

            $collection->add($this->tournamentTargetHydrator->hydrate(
                [
                    'round' => $row['round'],
                    'distance' => $row['distance'],
                    'stakes' => $stakes,
                ],
                $lane,
                $target,
            ));
        }

        return $collection;
    }

    /** @param array<string,int> $stakes */
    private function encodeStakes(array $stakes): string
    {
        return json_encode($stakes, JSON_THROW_ON_ERROR);
    }

    /** @return array<string,int> */
    private function decodeStakes(string $payload): array
    {
        try {
            /** @var array<string,int> $decoded */
            $decoded = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);

            return $decoded;
        } catch (JsonException) {
            return [];
        }
    }
}
