<?php

declare(strict_types=1);

namespace App\Tests\Fixtures;

use App\Domain\Entity\ArcheryGround;
use App\Domain\Entity\ArcheryGround\ShootingLane;
use App\Domain\Entity\ArcheryGround\Target;
use App\Domain\ValueObject\TargetType;
use Symfony\Component\Uid\Uuid;

final class ArcheryGroundSmallSized
{
    public static function create(): ArcheryGround
    {
        $archeryGround = new ArcheryGround(
            id: Uuid::v4()->toRfc4122(),
            name: 'Small Sized Shooting Range',
        );

        $archeryGround->addTarget(
            new Target(
                id: Uuid::v4()->toRfc4122(),
                type: TargetType::ANIMAL_GROUP_1,
                name: 'Deer Target',
                image: 'deer.png',
            ),
        );

        $archeryGround->addTarget(
            new Target(
                id: Uuid::v4()->toRfc4122(),
                type: TargetType::ANIMAL_GROUP_2,
                name: 'Boar Target',
                image: 'boar.png',
            ),
        );

        $archeryGround->addTarget(
            new Target(
                id: Uuid::v4()->toRfc4122(),
                type: TargetType::ANIMAL_GROUP_3,
                name: 'Bear Target',
                image: 'bear.png',
            ),
        );

        $archeryGround->addTarget(
            new Target(
                id: Uuid::v4()->toRfc4122(),
                type: TargetType::ANIMAL_GROUP_4,
                name: 'Wolf Target',
                image: 'wolf.png',
            ),
        );

        $archeryGround->addTarget(
            new Target(
                id: Uuid::v4()->toRfc4122(),
                type: TargetType::ANIMAL_GROUP_4,
                name: 'Rabbit Target',
                image: 'rabbit.png',
            ),
        );

        $archeryGround->addTarget(
            new Target(
                id: Uuid::v4()->toRfc4122(),
                type: TargetType::ANIMAL_GROUP_4,
                name: 'Marmot Target',
                image: 'marmot.png',
            ),
        );

        $archeryGround->addShootingLane(
            new ShootingLane(
                id: Uuid::v4()->toRfc4122(),
                name: 'Lane 1',
                maxDistance: 30.0,
            ),
        );

        $archeryGround->addShootingLane(
            new ShootingLane(
                id: Uuid::v4()->toRfc4122(),
                name: 'Lane 2',
                maxDistance: 50.0,
            ),
        );

        $archeryGround->addShootingLane(
            new ShootingLane(
                id: Uuid::v4()->toRfc4122(),
                name: 'Lane 3',
                maxDistance: 25.0,
            ),
        );

        $archeryGround->addShootingLane(
            new ShootingLane(
                id: Uuid::v4()->toRfc4122(),
                name: 'Lane 4',
                maxDistance: 23.0,
            ),
        );

        $archeryGround->addShootingLane(
            new ShootingLane(
                id: Uuid::v4()->toRfc4122(),
                name: 'Lane 5',
                maxDistance: 42.0,
            ),
        );

        return $archeryGround;
    }
}
