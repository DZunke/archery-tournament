<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

/**
 * Converts target zone sizes (in millimeters) to the appropriate target group.
 *
 * Ruleset:
 * - Group 1: > 250mm
 * - Group 2: 201 - 250mm
 * - Group 3: 150 - 200mm
 * - Group 4: < 150mm
 */
final class TargetZoneSizeConverter
{
    private const int GROUP_1_THRESHOLD = 250;
    private const int GROUP_2_MIN       = 201;
    private const int GROUP_3_MIN       = 150;

    public static function toTargetType(int $zoneSizeInMm): TargetType
    {
        if ($zoneSizeInMm > self::GROUP_1_THRESHOLD) {
            return TargetType::ANIMAL_GROUP_1;
        }

        if ($zoneSizeInMm >= self::GROUP_2_MIN) {
            return TargetType::ANIMAL_GROUP_2;
        }

        if ($zoneSizeInMm >= self::GROUP_3_MIN) {
            return TargetType::ANIMAL_GROUP_3;
        }

        return TargetType::ANIMAL_GROUP_4;
    }

    public static function isValid(int $zoneSizeInMm, TargetType $type): bool
    {
        return self::toTargetType($zoneSizeInMm) === $type;
    }
}
