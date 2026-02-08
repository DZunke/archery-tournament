<?php

declare(strict_types=1);

namespace App\Tests\Unit\Command\ArcheryGround;

use App\Application\Command\ArcheryGround\UpdateShootingLane;
use App\Application\Command\ArcheryGround\UpdateShootingLaneHandler;
use App\Domain\Entity\ArcheryGround;
use App\Domain\Entity\ArcheryGround\ShootingLane;
use App\Tests\Unit\Support\InMemoryArcheryGroundRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

#[CoversClass(UpdateShootingLaneHandler::class)]
final class UpdateShootingLaneHandlerTest extends TestCase
{
    #[Test]
    public function updatesShootingLane(): void
    {
        $repository = new InMemoryArcheryGroundRepository();
        $handler    = new UpdateShootingLaneHandler($repository);

        $groundId = Uuid::v4()->toRfc4122();
        $laneId   = Uuid::v4()->toRfc4122();

        $ground = new ArcheryGround($groundId, 'Test Ground');
        $ground->addShootingLane(new ShootingLane($laneId, 'Lane 1', 30.0));
        $repository->seed($ground);

        $result = $handler(new UpdateShootingLane($groundId, $laneId, 'Updated Lane', 45.0, true, 'Some notes'));

        self::assertTrue($result->success);
        self::assertSame('The shooting lane "Updated Lane" was updated.', $result->message);
        self::assertCount(1, $repository->updatedLanes);

        $updatedLane = $repository->updatedLanes[0];
        self::assertSame($groundId, $updatedLane['archeryGroundId']);
        self::assertSame($laneId, $updatedLane['laneId']);
        self::assertSame('Updated Lane', $updatedLane['name']);
        self::assertSame(45.0, $updatedLane['maxDistance']);
        self::assertTrue($updatedLane['forTrainingOnly']);
        self::assertSame('Some notes', $updatedLane['notes']);
    }

    #[Test]
    public function failsWhenArcheryGroundNotFound(): void
    {
        $repository = new InMemoryArcheryGroundRepository();
        $handler    = new UpdateShootingLaneHandler($repository);

        $result = $handler(new UpdateShootingLane(
            Uuid::v4()->toRfc4122(),
            Uuid::v4()->toRfc4122(),
            'Lane',
            30.0,
            false,
            '',
        ));

        self::assertFalse($result->success);
        self::assertSame('Archery ground not found.', $result->message);
    }

    #[Test]
    public function failsWhenLaneNotFound(): void
    {
        $repository = new InMemoryArcheryGroundRepository();
        $handler    = new UpdateShootingLaneHandler($repository);

        $groundId = Uuid::v4()->toRfc4122();
        $ground   = new ArcheryGround($groundId, 'Test Ground');
        $repository->seed($ground);

        $result = $handler(new UpdateShootingLane(
            $groundId,
            Uuid::v4()->toRfc4122(),
            'Lane',
            30.0,
            false,
            '',
        ));

        self::assertFalse($result->success);
        self::assertSame('Lane not found.', $result->message);
    }
}
