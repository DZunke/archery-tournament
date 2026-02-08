<?php

declare(strict_types=1);

namespace App\Tests\Unit\Support;

use App\Domain\Entity\ArcheryGround;
use App\Domain\Entity\ArcheryGround\Attachment;
use App\Domain\Entity\ArcheryGround\ShootingLane;
use App\Domain\Entity\ArcheryGround\Target;
use App\Domain\Repository\ArcheryGroundRepository;
use Symfony\Component\Uid\Uuid;

use function array_values;

final class InMemoryArcheryGroundRepository implements ArcheryGroundRepository
{
    /** @var array<string, ArcheryGround> */
    private array $grounds = [];

    /** @var list<ArcheryGround> */
    public array $saved = [];

    /** @var list<array{archeryGroundId: string, lane: ShootingLane}> */
    public array $addedLanes = [];

    /** @var list<string> */
    public array $removedLanes = [];

    /** @var list<array{archeryGroundId: string, laneId: string, name: string, maxDistance: float, forTrainingOnly: bool, notes: string}> */
    public array $updatedLanes = [];

    /** @var list<array{archeryGroundId: string, target: Target}> */
    public array $addedTargets = [];

    /** @var list<string> */
    public array $removedTargets = [];

    /** @var list<array{archeryGroundId: string, targetId: string, name: string, type: string, forTrainingOnly: bool, notes: string, imagePath: string|null, targetZoneSize: int|null}> */
    public array $updatedTargets = [];

    /** @var list<array{archeryGroundId: string, attachment: Attachment}> */
    public array $addedAttachments = [];

    /** @var list<string> */
    public array $removedAttachments = [];

    /** @var list<string> */
    public array $deleted = [];

    public function nextIdentity(): string
    {
        return Uuid::v4()->toRfc4122();
    }

    public function save(ArcheryGround $archeryGround): void
    {
        $this->grounds[$archeryGround->id()] = $archeryGround;
        $this->saved[]                       = $archeryGround;
    }

    public function find(string $id): ArcheryGround|null
    {
        return $this->grounds[$id] ?? null;
    }

    /** @return list<ArcheryGround> */
    public function findAll(): array
    {
        return array_values($this->grounds);
    }

    public function delete(string $id): void
    {
        $this->deleted[] = $id;
        unset($this->grounds[$id]);
    }

    public function addShootingLane(string $archeryGroundId, ShootingLane $lane): void
    {
        $this->addedLanes[] = [
            'archeryGroundId' => $archeryGroundId,
            'lane' => $lane,
        ];
    }

    public function removeShootingLane(string $laneId): void
    {
        $this->removedLanes[] = $laneId;
    }

    public function updateShootingLane(
        string $archeryGroundId,
        string $laneId,
        string $name,
        float $maxDistance,
        bool $forTrainingOnly,
        string $notes,
    ): void {
        $this->updatedLanes[] = [
            'archeryGroundId' => $archeryGroundId,
            'laneId' => $laneId,
            'name' => $name,
            'maxDistance' => $maxDistance,
            'forTrainingOnly' => $forTrainingOnly,
            'notes' => $notes,
        ];
    }

    public function addTarget(string $archeryGroundId, Target $target): void
    {
        $this->addedTargets[] = [
            'archeryGroundId' => $archeryGroundId,
            'target' => $target,
        ];
    }

    public function removeTarget(string $targetId): void
    {
        $this->removedTargets[] = $targetId;
    }

    public function updateTarget(
        string $archeryGroundId,
        string $targetId,
        string $name,
        string $type,
        bool $forTrainingOnly,
        string $notes,
        string|null $imagePath = null,
        int|null $targetZoneSize = null,
    ): void {
        $this->updatedTargets[] = [
            'archeryGroundId' => $archeryGroundId,
            'targetId' => $targetId,
            'name' => $name,
            'type' => $type,
            'forTrainingOnly' => $forTrainingOnly,
            'notes' => $notes,
            'imagePath' => $imagePath,
            'targetZoneSize' => $targetZoneSize,
        ];
    }

    public function seed(ArcheryGround $archeryGround): void
    {
        $this->grounds[$archeryGround->id()] = $archeryGround;
    }

    public function addAttachment(string $archeryGroundId, Attachment $attachment): void
    {
        $this->addedAttachments[] = [
            'archeryGroundId' => $archeryGroundId,
            'attachment' => $attachment,
        ];
    }

    public function removeAttachment(string $attachmentId): void
    {
        $this->removedAttachments[] = $attachmentId;
    }
}
