<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

enum TargetType: string
{
    case ANIMAL_GROUP_1 = 'animal_group_1';
    case ANIMAL_GROUP_2 = 'animal_group_2';
    case ANIMAL_GROUP_3 = 'animal_group_3';
    case ANIMAL_GROUP_4 = 'animal_group_4';
}
