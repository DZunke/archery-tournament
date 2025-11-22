<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

enum Ruleset: string
{
    case DSB_3D = 'DSB_3D';

    /** @return list<TargetType> */
    public function allowedTargetTypes(): array
    {
        return match ($this) {
            self::DSB_3D => [
                TargetType::ANIMAL_GROUP_1,
                TargetType::ANIMAL_GROUP_2,
                TargetType::ANIMAL_GROUP_3,
                TargetType::ANIMAL_GROUP_4,
            ],
        };
    }
}
