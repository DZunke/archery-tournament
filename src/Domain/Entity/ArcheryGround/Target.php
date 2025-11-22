<?php

declare(strict_types=1);

namespace App\Domain\Entity\ArcheryGround;

use App\Domain\ValueObject\TargetType;
use Webmozart\Assert\Assert;

final class Target
{
    public function __construct(
        private readonly TargetType $type,
        private readonly string $name,
        private readonly string $image,
    ) {
        Assert::notEmpty($this->name, 'The target name must not be empty.');
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
}
