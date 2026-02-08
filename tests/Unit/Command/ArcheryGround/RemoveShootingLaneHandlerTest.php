<?php

declare(strict_types=1);

namespace App\Tests\Unit\Command\ArcheryGround;

use App\Application\Command\ArcheryGround\RemoveShootingLane;
use App\Application\Command\ArcheryGround\RemoveShootingLaneHandler;
use App\Domain\Entity\ArcheryGround;
use App\Domain\Entity\ArcheryGround\ShootingLane;
use App\Tests\Unit\Support\InMemoryArcheryGroundRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class RemoveShootingLaneHandlerTest extends TestCase
{
    public function testRemovesShootingLane(): void
    {
        $repository = new InMemoryArcheryGroundRepository();
        $handler    = new RemoveShootingLaneHandler($repository);

        $groundId = Uuid::v4()->toRfc4122();
        $laneId   = Uuid::v4()->toRfc4122();

        $ground = new ArcheryGround($groundId, 'Forest Range');
        $ground->addShootingLane(new ShootingLane($laneId, 'Lane North', 30.0));
        $repository->seed($ground);

        $result = $handler(new RemoveShootingLane($groundId, $laneId));

        self::assertTrue($result->success);
        self::assertSame('The shooting lane "Lane North" was removed.', $result->message);
        self::assertSame([$laneId], $repository->removedLanes);
    }
}
