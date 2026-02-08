<?php

declare(strict_types=1);

namespace App\Domain\Entity\ArcheryGround;

use App\Domain\ValueObject\TargetType;
use Webmozart\Assert\Assert;

final class Target
{
    public function __construct(
        private readonly string $id,
        private readonly TargetType $type,
        private readonly string $name,
        private readonly string $image,
        private readonly bool $forTrainingOnly = false,
        private readonly string $notes = '',
        private readonly int|null $targetZoneSize = null,
    ) {
        Assert::uuid($this->id, 'The target id must be a valid UUID.');
        Assert::notEmpty($this->name, 'The target name must not be empty.');

        if ($this->targetZoneSize === null) {
            return;
        }

        Assert::greaterThan($this->targetZoneSize, 0, 'Target zone size must be greater than 0.');
    }

    public function id(): string
    {
        return $this->id;
    }

    public function type(): TargetType
    {
        return $this->type;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function image(): string
    {
        return $this->image;
    }

    public function forTrainingOnly(): bool
    {
        return $this->forTrainingOnly;
    }

    public function notes(): string
    {
        return $this->notes;
    }

    public function targetZoneSize(): int|null
    {
        return $this->targetZoneSize;
    }
}
