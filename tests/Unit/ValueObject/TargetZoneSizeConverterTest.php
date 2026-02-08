<?php

declare(strict_types=1);

namespace App\Tests\Unit\ValueObject;

use App\Domain\ValueObject\TargetType;
use App\Domain\ValueObject\TargetZoneSizeConverter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(TargetZoneSizeConverter::class)]
final class TargetZoneSizeConverterTest extends TestCase
{
    #[Test]
    #[DataProvider('provideZoneSizeToTypeMapping')]
    public function convertsZoneSizeToCorrectTargetType(int $zoneSizeInMm, TargetType $expectedType): void
    {
        $result = TargetZoneSizeConverter::toTargetType($zoneSizeInMm);

        self::assertSame($expectedType, $result);
    }

    /** @return iterable<string, array{int, TargetType}> */
    public static function provideZoneSizeToTypeMapping(): iterable
    {
        // Group 1: > 250mm
        yield 'Group 1 - 251mm' => [251, TargetType::ANIMAL_GROUP_1];
        yield 'Group 1 - 300mm' => [300, TargetType::ANIMAL_GROUP_1];
        yield 'Group 1 - 500mm' => [500, TargetType::ANIMAL_GROUP_1];

        // Group 2: 201 - 250mm
        yield 'Group 2 - 250mm (upper boundary)' => [250, TargetType::ANIMAL_GROUP_2];
        yield 'Group 2 - 225mm' => [225, TargetType::ANIMAL_GROUP_2];
        yield 'Group 2 - 201mm (lower boundary)' => [201, TargetType::ANIMAL_GROUP_2];

        // Group 3: 150 - 200mm
        yield 'Group 3 - 200mm (upper boundary)' => [200, TargetType::ANIMAL_GROUP_3];
        yield 'Group 3 - 175mm' => [175, TargetType::ANIMAL_GROUP_3];
        yield 'Group 3 - 150mm (lower boundary)' => [150, TargetType::ANIMAL_GROUP_3];

        // Group 4: < 150mm
        yield 'Group 4 - 149mm (upper boundary)' => [149, TargetType::ANIMAL_GROUP_4];
        yield 'Group 4 - 100mm' => [100, TargetType::ANIMAL_GROUP_4];
        yield 'Group 4 - 50mm' => [50, TargetType::ANIMAL_GROUP_4];
        yield 'Group 4 - 1mm' => [1, TargetType::ANIMAL_GROUP_4];
    }

    #[Test]
    #[DataProvider('provideValidationCases')]
    public function validatesZoneSizeAgainstType(int $zoneSizeInMm, TargetType $type, bool $expectedValid): void
    {
        $result = TargetZoneSizeConverter::isValid($zoneSizeInMm, $type);

        self::assertSame($expectedValid, $result);
    }

    /** @return iterable<string, array{int, TargetType, bool}> */
    public static function provideValidationCases(): iterable
    {
        yield 'Valid Group 1 match' => [300, TargetType::ANIMAL_GROUP_1, true];
        yield 'Invalid Group 1 with Group 2' => [300, TargetType::ANIMAL_GROUP_2, false];
        yield 'Valid Group 2 match' => [220, TargetType::ANIMAL_GROUP_2, true];
        yield 'Invalid Group 2 with Group 3' => [220, TargetType::ANIMAL_GROUP_3, false];
        yield 'Valid Group 3 match' => [175, TargetType::ANIMAL_GROUP_3, true];
        yield 'Invalid Group 3 with Group 4' => [175, TargetType::ANIMAL_GROUP_4, false];
        yield 'Valid Group 4 match' => [100, TargetType::ANIMAL_GROUP_4, true];
        yield 'Invalid Group 4 with Group 1' => [100, TargetType::ANIMAL_GROUP_1, false];
    }
}
