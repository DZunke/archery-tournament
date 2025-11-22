<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

use Webmozart\Assert\Assert;

use function in_array;

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

    /** @return list<TargetType> */
    public function requiredTargetTypes(): array
    {
        return $this->allowedTargetTypes();
    }

    /** @return array{min: float, max: float} */
    public function distanceRange(TargetType $targetType): array
    {
        Assert::true(
            in_array($targetType, $this->allowedTargetTypes(), true),
            'Target type is not allowed for this ruleset.',
        );

        return match ($this) {
            self::DSB_3D => match ($targetType) {
                TargetType::ANIMAL_GROUP_1 => ['min' => 30.0, 'max' => 45.0],
                TargetType::ANIMAL_GROUP_2 => ['min' => 20.0, 'max' => 35.0],
                TargetType::ANIMAL_GROUP_3 => ['min' => 10.0, 'max' => 25.0],
                TargetType::ANIMAL_GROUP_4 => ['min' => 5.0, 'max' => 15.0],
            },
        };
    }
}
