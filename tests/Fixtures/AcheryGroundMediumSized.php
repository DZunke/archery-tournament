<?php

declare(strict_types=1);

namespace App\Tests\Fixtures;

use App\Domain\Entity\ArcheryGround;
use App\Domain\Entity\ArcheryGround\ShootingLane;
use App\Domain\Entity\ArcheryGround\Target;
use App\Domain\ValueObject\TargetType;
use Symfony\Component\Uid\Uuid;

final class AcheryGroundMediumSized
{
    public static function create(): ArcheryGround
    {
        $archeryGround = new ArcheryGround(
            id: Uuid::v4()->toRfc4122(),
            name: 'Medium Sized Shooting Range',
        );

        foreach (self::getTargets() as $targetData) {
            $archeryGround->addTarget(
                new Target(
                    id: $targetData['id'],
                    type: $targetData['type'],
                    name: $targetData['name'],
                    image: $targetData['image'],
                ),
            );
        }

        foreach (self::getShootingLanes() as $laneData) {
            $archeryGround->addShootingLane(
                new ShootingLane(
                    id: $laneData['id'],
                    name: $laneData['name'],
                    maxDistance: $laneData['maxDistance'],
                ),
            );
        }

        return $archeryGround;
    }

    /** @return list<array{id: string, type: TargetType, name: string, image: string}> */
    private static function getTargets(): array
    {
        return [
            ['id' => Uuid::v4()->toRfc4122(), 'type' => TargetType::ANIMAL_GROUP_1, 'name' => 'Deer', 'image' => 'deer.png'],
            ['id' => Uuid::v4()->toRfc4122(), 'type' => TargetType::ANIMAL_GROUP_1, 'name' => 'Bear', 'image' => 'bear.png'],
            ['id' => Uuid::v4()->toRfc4122(), 'type' => TargetType::ANIMAL_GROUP_1, 'name' => 'Bison', 'image' => 'bison.png'],
            ['id' => Uuid::v4()->toRfc4122(), 'type' => TargetType::ANIMAL_GROUP_1, 'name' => 'Elk', 'image' => 'elk.png'],

            ['id' => Uuid::v4()->toRfc4122(), 'type' => TargetType::ANIMAL_GROUP_2, 'name' => 'Wolf', 'image' => 'wolf.png'],
            ['id' => Uuid::v4()->toRfc4122(), 'type' => TargetType::ANIMAL_GROUP_2, 'name' => 'Lynx', 'image' => 'lynx.png'],
            ['id' => Uuid::v4()->toRfc4122(), 'type' => TargetType::ANIMAL_GROUP_2, 'name' => 'Fox', 'image' => 'fox.png'],
            ['id' => Uuid::v4()->toRfc4122(), 'type' => TargetType::ANIMAL_GROUP_2, 'name' => 'Ibex', 'image' => 'ibex.png'],
            ['id' => Uuid::v4()->toRfc4122(), 'type' => TargetType::ANIMAL_GROUP_2, 'name' => 'Chamois', 'image' => 'chamois.png'],
            ['id' => Uuid::v4()->toRfc4122(), 'type' => TargetType::ANIMAL_GROUP_2, 'name' => 'Wild Boar', 'image' => 'wild_boar.png'],

            ['id' => Uuid::v4()->toRfc4122(), 'type' => TargetType::ANIMAL_GROUP_3, 'name' => 'Hare', 'image' => 'hare.png'],
            ['id' => Uuid::v4()->toRfc4122(), 'type' => TargetType::ANIMAL_GROUP_3, 'name' => 'Rabbit', 'image' => 'rabbit.png'],
            ['id' => Uuid::v4()->toRfc4122(), 'type' => TargetType::ANIMAL_GROUP_3, 'name' => 'Marmot', 'image' => 'marmot.png'],
            ['id' => Uuid::v4()->toRfc4122(), 'type' => TargetType::ANIMAL_GROUP_3, 'name' => 'Turkey', 'image' => 'turkey.png'],
            ['id' => Uuid::v4()->toRfc4122(), 'type' => TargetType::ANIMAL_GROUP_3, 'name' => 'Owl', 'image' => 'owl.png'],
            ['id' => Uuid::v4()->toRfc4122(), 'type' => TargetType::ANIMAL_GROUP_3, 'name' => 'Eagle', 'image' => 'eagle.png'],
            ['id' => Uuid::v4()->toRfc4122(), 'type' => TargetType::ANIMAL_GROUP_3, 'name' => 'Beaver', 'image' => 'beaver.png'],

            ['id' => Uuid::v4()->toRfc4122(), 'type' => TargetType::ANIMAL_GROUP_4, 'name' => 'Rat Family', 'image' => 'rat_family.png'],
            ['id' => Uuid::v4()->toRfc4122(), 'type' => TargetType::ANIMAL_GROUP_4, 'name' => 'Raven', 'image' => 'raven.png'],
            ['id' => Uuid::v4()->toRfc4122(), 'type' => TargetType::ANIMAL_GROUP_4, 'name' => 'Falcon', 'image' => 'falcon.png'],
            ['id' => Uuid::v4()->toRfc4122(), 'type' => TargetType::ANIMAL_GROUP_4, 'name' => 'Two Piglets', 'image' => 'two_piglets.png'],
            ['id' => Uuid::v4()->toRfc4122(), 'type' => TargetType::ANIMAL_GROUP_4, 'name' => 'Baby Rabbit', 'image' => 'baby_rabbit.png'],
        ];
    }

    /** @return list<array{id: string, name: string, maxDistance: float}> */
    private static function getShootingLanes(): array
    {
        return [
            ['id' => Uuid::v4()->toRfc4122(), 'name' => 'Lane 1', 'maxDistance' => 30.0],
            ['id' => Uuid::v4()->toRfc4122(), 'name' => 'Lane 2', 'maxDistance' => 50.0],
            ['id' => Uuid::v4()->toRfc4122(), 'name' => 'Lane 3', 'maxDistance' => 25.0],
            ['id' => Uuid::v4()->toRfc4122(), 'name' => 'Lane 4', 'maxDistance' => 9.0],
            ['id' => Uuid::v4()->toRfc4122(), 'name' => 'Lane 5', 'maxDistance' => 42.0],
            ['id' => Uuid::v4()->toRfc4122(), 'name' => 'Lane 6', 'maxDistance' => 15.0],
            ['id' => Uuid::v4()->toRfc4122(), 'name' => 'Lane 7', 'maxDistance' => 33.0],
            ['id' => Uuid::v4()->toRfc4122(), 'name' => 'Lane 8', 'maxDistance' => 7.0],
            ['id' => Uuid::v4()->toRfc4122(), 'name' => 'Lane 9', 'maxDistance' => 40.0],
            ['id' => Uuid::v4()->toRfc4122(), 'name' => 'Lane 10', 'maxDistance' => 20.0],
            ['id' => Uuid::v4()->toRfc4122(), 'name' => 'Lane 11', 'maxDistance' => 27.0],
            ['id' => Uuid::v4()->toRfc4122(), 'name' => 'Lane 12', 'maxDistance' => 6.0],
        ];
    }
}
