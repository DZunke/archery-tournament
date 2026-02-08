<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Dbal;

use App\Domain\Entity\ArcheryGround;
use App\Domain\Entity\ArcheryGround\ShootingLane;
use App\Domain\Entity\ArcheryGround\Target;
use App\Domain\Repository\ArcheryGroundRepository;
use App\Infrastructure\Persistence\Dbal\Hydrator\ArcheryGroundHydrator;
use App\Infrastructure\Persistence\Dbal\Hydrator\ShootingLaneHydrator;
use App\Infrastructure\Persistence\Dbal\Hydrator\TargetHydrator;
use Doctrine\DBAL\Connection;
use Symfony\Component\Uid\Uuid;

use function strnatcasecmp;
use function usort;

final readonly class DbalArcheryGroundRepository implements ArcheryGroundRepository
{
    public function __construct(
        private Connection $connection,
        private ArcheryGroundHydrator $archeryGroundHydrator,
        private ShootingLaneHydrator $shootingLaneHydrator,
        private TargetHydrator $targetHydrator,
    ) {
    }

    public function nextIdentity(): string
    {
        return Uuid::v4()->toRfc4122();
    }

    public function save(ArcheryGround $archeryGround): void
    {
        $exists = $this->connection->fetchOne(
            'SELECT 1 FROM archery_grounds WHERE id = ?',
            [$archeryGround->id()],
        );

        if ($exists !== false) {
            $this->connection->executeStatement(
                'UPDATE archery_grounds SET name = ? WHERE id = ?',
                [$archeryGround->name(), $archeryGround->id()],
            );

            return;
        }

        $this->connection->insert('archery_grounds', [
            'id' => $archeryGround->id(),
            'name' => $archeryGround->name(),
        ]);
    }

    public function find(string $id): ArcheryGround|null
    {
        $row = $this->connection->fetchAssociative(
            'SELECT id, name FROM archery_grounds WHERE id = ?',
            [$id],
        );

        if ($row === false) {
            return null;
        }

        $row       = [
            'id' => (string) $row['id'],
            'name' => (string) $row['name'],
        ];
        $lanesRows = $this->connection->fetchAllAssociative(
            'SELECT id, name, max_distance, for_training_only, notes FROM shooting_lanes WHERE archery_ground_id = ?',
            [$id],
        );

        $targetRows = $this->connection->fetchAllAssociative(
            'SELECT id, type, name, image FROM targets WHERE archery_ground_id = ? ORDER BY name',
            [$id],
        );

        $lanes = [];
        foreach ($lanesRows as $laneRow) {
            /** @var array{id: string, name: string, max_distance: float|string, for_training_only: int|string|bool, notes: string} $laneRow */
            $lanes[] = $this->shootingLaneHydrator->hydrate($laneRow);
        }

        usort(
            $lanes,
            static fn (ShootingLane $a, ShootingLane $b): int => strnatcasecmp($a->name(), $b->name()),
        );

        $targets = [];
        foreach ($targetRows as $targetRow) {
            /** @var array{id: string, type: string, name: string, image: string} $targetRow */
            $targets[] = $this->targetHydrator->hydrate($targetRow);
        }

        return $this->archeryGroundHydrator->hydrate($row, $lanes, $targets);
    }

    /** @return list<ArcheryGround> */
    public function findAll(): array
    {
        $rows    = $this->connection->fetchAllAssociative('SELECT id FROM archery_grounds ORDER BY name');
        $grounds = [];

        foreach ($rows as $row) {
            $ground = $this->find($row['id']);
            if (! ($ground instanceof ArcheryGround)) {
                continue;
            }

            $grounds[] = $ground;
        }

        return $grounds;
    }

    public function delete(string $id): void
    {
        $this->connection->executeStatement('DELETE FROM targets WHERE archery_ground_id = ?', [$id]);
        $this->connection->executeStatement('DELETE FROM shooting_lanes WHERE archery_ground_id = ?', [$id]);
        $this->connection->executeStatement('DELETE FROM archery_grounds WHERE id = ?', [$id]);
    }

    public function addShootingLane(string $archeryGroundId, ShootingLane $lane): void
    {
        $this->connection->insert('shooting_lanes', [
            'id' => $lane->id(),
            'archery_ground_id' => $archeryGroundId,
            'name' => $lane->name(),
            'max_distance' => $lane->maxDistance(),
            'for_training_only' => $lane->forTrainingOnly() ? 1 : 0,
            'notes' => $lane->notes(),
        ]);
    }

    public function removeShootingLane(string $laneId): void
    {
        $this->connection->executeStatement('DELETE FROM shooting_lanes WHERE id = ?', [$laneId]);
    }

    public function updateShootingLane(
        string $archeryGroundId,
        string $laneId,
        string $name,
        float $maxDistance,
        bool $forTrainingOnly,
        string $notes,
    ): void {
        $this->connection->executeStatement(
            'UPDATE shooting_lanes SET name = ?, max_distance = ?, for_training_only = ?, notes = ? WHERE id = ? AND archery_ground_id = ?',
            [$name, $maxDistance, $forTrainingOnly ? 1 : 0, $notes, $laneId, $archeryGroundId],
        );
    }

    public function addTarget(string $archeryGroundId, Target $target): void
    {
        $this->connection->insert('targets', [
            'id' => $target->id(),
            'archery_ground_id' => $archeryGroundId,
            'type' => $target->type()->value,
            'name' => $target->name(),
            'image' => $target->image(),
        ]);
    }

    public function removeTarget(string $targetId): void
    {
        $this->connection->executeStatement('DELETE FROM targets WHERE id = ?', [$targetId]);
    }

    public function updateTarget(
        string $archeryGroundId,
        string $targetId,
        string $name,
        string $type,
        string|null $imagePath = null,
    ): void {
        if ($imagePath !== null) {
            $this->connection->executeStatement(
                'UPDATE targets SET name = ?, type = ?, image = ? WHERE id = ? AND archery_ground_id = ?',
                [$name, $type, $imagePath, $targetId, $archeryGroundId],
            );

            return;
        }

        $this->connection->executeStatement(
            'UPDATE targets SET name = ?, type = ? WHERE id = ? AND archery_ground_id = ?',
            [$name, $type, $targetId, $archeryGroundId],
        );
    }
}
