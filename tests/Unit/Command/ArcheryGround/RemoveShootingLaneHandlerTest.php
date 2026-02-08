<?php

declare(strict_types=1);

namespace App\Tests\Unit\Command\ArcheryGround;

use App\Application\Command\ArcheryGround\RemoveShootingLane;
use App\Application\Command\ArcheryGround\RemoveShootingLaneHandler;
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

        $result = $handler(new RemoveShootingLane($groundId, $laneId));

        self::assertTrue($result->success);
        self::assertSame('Lane removed.', $result->message);
        self::assertSame([$laneId], $repository->removedLanes);
    }
}
