<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

enum TargetType: string
{
    case ANIMAL_GROUP_1 = 'animal_group_1';
    case ANIMAL_GROUP_2 = 'animal_group_2';
    case ANIMAL_GROUP_3 = 'animal_group_3';
    case ANIMAL_GROUP_4 = 'animal_group_4';

    public function label(): string
    {
        return match ($this) {
            self::ANIMAL_GROUP_1 => 'Target Group 1',
            self::ANIMAL_GROUP_2 => 'Target Group 2',
            self::ANIMAL_GROUP_3 => 'Target Group 3',
            self::ANIMAL_GROUP_4 => 'Target Group 4',
        };
    }

    public function zoneSizeDescription(): string
    {
        return match ($this) {
            self::ANIMAL_GROUP_1 => 'Large targets with kill zone > 250mm',
            self::ANIMAL_GROUP_2 => 'Medium targets with kill zone 201-250mm',
            self::ANIMAL_GROUP_3 => 'Small targets with kill zone 150-200mm',
            self::ANIMAL_GROUP_4 => 'Very small targets with kill zone < 150mm',
        };
    }

    /** @return array{min: int|null, max: int|null} */
    public function zoneSizeRange(): array
    {
        return match ($this) {
            self::ANIMAL_GROUP_1 => ['min' => 251, 'max' => null],
            self::ANIMAL_GROUP_2 => ['min' => 201, 'max' => 250],
            self::ANIMAL_GROUP_3 => ['min' => 150, 'max' => 200],
            self::ANIMAL_GROUP_4 => ['min' => null, 'max' => 149],
        };
    }
}
