<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

use Webmozart\Assert\Assert;

use function array_map;
use function assert;
use function count;
use function in_array;
use function max;
use function min;
use function usort;

enum Ruleset: string
{
    case DSB_3D   = 'DSB_3D';
    case FREEHAND = 'FREEHAND';

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
            self::FREEHAND => TargetType::cases(),
        };
    }

    public function name(): string
    {
        return match ($this) {
            self::DSB_3D => 'DSB 3D',
            self::FREEHAND => 'Freehand',
        };
    }

    /** @return list<TargetType> */
    public function requiredTargetTypes(): array
    {
        return $this->allowedTargetTypes();
    }

    public function supportsTargetGroupBalancing(): bool
    {
        return $this !== self::FREEHAND;
    }

    public function getMaxStakeDistance(TargetType $targetType): float
    {
        Assert::true(
            in_array($targetType, $this->allowedTargetTypes(), true),
            'Target type is not allowed for this ruleset.',
        );

        $ranges       = $this->stakeDistanceRanges($targetType);
        $maxDistances = array_map(static fn (array $range) => $range['max'], $ranges);
        assert(count($maxDistances) > 0);

        return max($maxDistances);
    }

    public function getOverallMinStakeDistance(): float
    {
        $minDistances = array_map(
            $this->getMinStakeDistance(...),
            $this->allowedTargetTypes(),
        );

        assert(count($minDistances) > 0);

        return min($minDistances);
    }

    public function getMinStakeDistance(TargetType $targetType): float
    {
        Assert::true(
            in_array($targetType, $this->allowedTargetTypes(), true),
            'Target type is not allowed for this ruleset.',
        );

        $ranges       = $this->stakeDistanceRanges($targetType);
        $maxDistances = array_map(static fn (array $range) => $range['min'], $ranges);
        assert(count($maxDistances) > 0);

        return min($maxDistances);
    }

    /**
     * Returns the minimum lane distance required to support all stakes of a target type.
     */
    public function getRequiredMinStakeDistance(TargetType $targetType): float
    {
        Assert::true(
            in_array($targetType, $this->allowedTargetTypes(), true),
            'Target type is not allowed for this ruleset.',
        );

        $ranges       = $this->stakeDistanceRanges($targetType);
        $minDistances = array_map(static fn (array $range) => $range['min'], $ranges);
        assert(count($minDistances) > 0);

        return max($minDistances);
    }

    /** @return array<string, array{min: float, max: float}> */
    public function stakeDistanceRanges(TargetType $targetType): array
    {
        Assert::true(
            in_array($targetType, $this->allowedTargetTypes(), true),
            'Target type is not allowed for this ruleset.',
        );

        return match ($this) {
            self::DSB_3D => match ($targetType) {
                TargetType::ANIMAL_GROUP_1 => [
                    'red' => ['min' => 30.0, 'max' => 45.0],
                    'blue' => ['min' => 20.0, 'max' => 30.0],
                    'yellow' => ['min' => 15.0, 'max' => 15.0],
                ],
                TargetType::ANIMAL_GROUP_2 => [
                    'red' => ['min' => 20.0, 'max' => 35.0],
                    'blue' => ['min' => 15.0, 'max' => 25.0],
                    'yellow' => ['min' => 12.0, 'max' => 12.0],
                ],
                TargetType::ANIMAL_GROUP_3 => [
                    'red' => ['min' => 10.0, 'max' => 25.0],
                    'blue' => ['min' => 10.0, 'max' => 20.0],
                    'yellow' => ['min' => 7.0, 'max' => 7.0],
                ],
                TargetType::ANIMAL_GROUP_4 => [
                    'red' => ['min' => 5.0, 'max' => 15.0],
                    'blue' => ['min' => 5.0, 'max' => 15.0],
                    'yellow' => ['min' => 5.0, 'max' => 5.0],
                ],
            },
            self::FREEHAND => match ($targetType) {
                TargetType::ANIMAL_GROUP_1,
                TargetType::ANIMAL_GROUP_2,
                TargetType::ANIMAL_GROUP_3,
                TargetType::ANIMAL_GROUP_4 => [
                    'red' => ['min' => 0.0, 'max' => 999.0],
                    'blue' => ['min' => 0.0, 'max' => 999.0],
                    'yellow' => ['min' => 0.0, 'max' => 999.0],
                ],
            },
        };
    }

    /** @return TargetType[] */
    public function targetTypesOrderedByMinDistance(): array
    {
        $targetTypes = $this->allowedTargetTypes();

        usort(
            $targetTypes,
            fn (TargetType $a, TargetType $b) => $this->getMinStakeDistance($a) <=> $this->getMinStakeDistance($b),
        );

        return $targetTypes;
    }

    /** @return TargetType[] */
    public function targetTypesOrderedByMaxDistance(): array
    {
        $targetTypes = $this->allowedTargetTypes();

        usort(
            $targetTypes,
            fn (TargetType $a, TargetType $b) => $this->getMaxStakeDistance($b) <=> $this->getMaxStakeDistance($a),
        );

        return $targetTypes;
    }
}
