<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\Entity\ArcheryGround\ShootingLane;
use App\Domain\Entity\ArcheryGround\Target;
use App\Domain\ValueObject\TargetType;
use Symfony\Component\Uid\Uuid;
use Webmozart\Assert\Assert;

use function array_filter;
use function array_values;
use function count;

final class ArcheryGround
{
    /**
     * @param list<Target>       $targetStorage
     * @param list<ShootingLane> $shootingLanes
     */
    public function __construct(
        private readonly string $id,
        private readonly string $name,
        private array $targetStorage = [],
        private array $shootingLanes = [],
    ) {
        Assert::uuid($this->id, 'The archery ground id must be a valid UUID.');
        Assert::notEmpty($this->name, 'The archery ground name must not be empty.');
    }

    public function create(string $name): self
    {
        return new self(
            id: Uuid::v4()->toRfc4122(),
            name: $name,
        );
    }

    public function id(): string
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    /** @return list<Target> */
    public function targetStorage(): array
    {
        return $this->targetStorage;
    }

    /** @return list<Target> */
    public function targetStorageByType(TargetType $targetType): array
    {
        return array_values(array_filter(
            $this->targetStorage,
            static fn (Target $target) => $target->type() === $targetType,
        ));
    }

    public function addTarget(Target $target): void
    {
        $this->targetStorage[] = $target;
    }

    /** @return list<ShootingLane> */
    public function shootingLanes(): array
    {
        return $this->shootingLanes;
    }

    public function numberOfShootingLanes(): int
    {
        return count($this->shootingLanes);
    }

    public function addShootingLane(ShootingLane $shootingLane): void
    {
        $this->shootingLanes[] = $shootingLane;
    }
}
