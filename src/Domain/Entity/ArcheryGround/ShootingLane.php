<?php

declare(strict_types=1);

namespace App\Domain\Entity\ArcheryGround;

use Webmozart\Assert\Assert;

final class ShootingLane
{
    public function __construct(
        private readonly string $name,
        private readonly float $maxDistance,
    ) {
        Assert::notEmpty($this->name, 'The shooting lane name must not be empty.');
        Assert::greaterThan($this->maxDistance, 0, 'The maximum distance must be greater than zero.');
    }

    public function name(): string
    {
        return $this->name;
    }

    public function maxDistance(): float
    {
        return $this->maxDistance;
    }
}
