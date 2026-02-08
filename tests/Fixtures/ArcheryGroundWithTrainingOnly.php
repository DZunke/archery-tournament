<?php

declare(strict_types=1);

namespace App\Tests\Fixtures;

use App\Domain\Entity\ArcheryGround;
use App\Domain\Entity\ArcheryGround\ShootingLane;
use App\Domain\Entity\ArcheryGround\Target;
use App\Domain\ValueObject\TargetType;
use Symfony\Component\Uid\Uuid;

/**
 * Creates an archery ground with a mix of competition and training-only lanes and targets.
 *
 * This fixture is designed to test the tournament generation filtering behavior:
 * - When includeTrainingOnly is false (default): only 3 competition lanes and 4 competition targets are available
 * - When includeTrainingOnly is true: all 5 lanes and 8 targets are available
 */
final class ArcheryGroundWithTrainingOnly
{
    public static function create(): ArcheryGround
    {
        $archeryGround = new ArcheryGround(
            id: Uuid::v4()->toRfc4122(),
            name: 'Mixed Training and Competition Range',
        );

        // Competition targets (4 targets - one per group)
        $archeryGround->addTarget(
            new Target(
                id: Uuid::v4()->toRfc4122(),
                type: TargetType::ANIMAL_GROUP_1,
                name: 'Competition Deer',
                image: 'deer.png',
                forTrainingOnly: false,
            ),
        );

        $archeryGround->addTarget(
            new Target(
                id: Uuid::v4()->toRfc4122(),
                type: TargetType::ANIMAL_GROUP_2,
                name: 'Competition Wolf',
                image: 'wolf.png',
                forTrainingOnly: false,
            ),
        );

        $archeryGround->addTarget(
            new Target(
                id: Uuid::v4()->toRfc4122(),
                type: TargetType::ANIMAL_GROUP_3,
                name: 'Competition Rabbit',
                image: 'rabbit.png',
                forTrainingOnly: false,
            ),
        );

        $archeryGround->addTarget(
            new Target(
                id: Uuid::v4()->toRfc4122(),
                type: TargetType::ANIMAL_GROUP_4,
                name: 'Competition Raven',
                image: 'raven.png',
                forTrainingOnly: false,
            ),
        );

        // Training-only targets (4 additional targets)
        $archeryGround->addTarget(
            new Target(
                id: Uuid::v4()->toRfc4122(),
                type: TargetType::ANIMAL_GROUP_1,
                name: 'Training Bear',
                image: 'bear.png',
                forTrainingOnly: true,
            ),
        );

        $archeryGround->addTarget(
            new Target(
                id: Uuid::v4()->toRfc4122(),
                type: TargetType::ANIMAL_GROUP_2,
                name: 'Training Fox',
                image: 'fox.png',
                forTrainingOnly: true,
            ),
        );

        $archeryGround->addTarget(
            new Target(
                id: Uuid::v4()->toRfc4122(),
                type: TargetType::ANIMAL_GROUP_3,
                name: 'Training Owl',
                image: 'owl.png',
                forTrainingOnly: true,
            ),
        );

        $archeryGround->addTarget(
            new Target(
                id: Uuid::v4()->toRfc4122(),
                type: TargetType::ANIMAL_GROUP_4,
                name: 'Training Rat',
                image: 'rat.png',
                forTrainingOnly: true,
            ),
        );

        // Competition lanes (3 lanes with sufficient distances)
        $archeryGround->addShootingLane(
            new ShootingLane(
                id: Uuid::v4()->toRfc4122(),
                name: 'Competition Lane 1',
                maxDistance: 45.0,
                forTrainingOnly: false,
            ),
        );

        $archeryGround->addShootingLane(
            new ShootingLane(
                id: Uuid::v4()->toRfc4122(),
                name: 'Competition Lane 2',
                maxDistance: 30.0,
                forTrainingOnly: false,
            ),
        );

        $archeryGround->addShootingLane(
            new ShootingLane(
                id: Uuid::v4()->toRfc4122(),
                name: 'Competition Lane 3',
                maxDistance: 25.0,
                forTrainingOnly: false,
            ),
        );

        $archeryGround->addShootingLane(
            new ShootingLane(
                id: Uuid::v4()->toRfc4122(),
                name: 'Competition Lane 4',
                maxDistance: 15.0,
                forTrainingOnly: false,
            ),
        );

        // Training-only lanes (2 lanes)
        $archeryGround->addShootingLane(
            new ShootingLane(
                id: Uuid::v4()->toRfc4122(),
                name: 'Training Lane 1',
                maxDistance: 50.0,
                forTrainingOnly: true,
            ),
        );

        $archeryGround->addShootingLane(
            new ShootingLane(
                id: Uuid::v4()->toRfc4122(),
                name: 'Training Lane 2',
                maxDistance: 35.0,
                forTrainingOnly: true,
            ),
        );

        return $archeryGround;
    }
}
