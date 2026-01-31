<?php

declare(strict_types=1);

namespace App\Tests\Unit\Command\ArcheryGround;

use App\Application\Command\ArcheryGround\AddShootingLane;
use App\Application\Command\ArcheryGround\AddShootingLaneHandler;
use App\Tests\Unit\Support\InMemoryArcheryGroundRepository;
use PHPUnit\Framework\TestCase;

final class AddShootingLaneHandlerTest extends TestCase
{
    public function testAddsShootingLane(): void
    {
        $repository = new InMemoryArcheryGroundRepository();
        $handler    = new AddShootingLaneHandler($repository);

        $result = $handler(new AddShootingLane('ground-id', 'Lane 1', 42.5));

        self::assertTrue($result->success);
        self::assertCount(1, $repository->addedLanes);

        $addedLane = $repository->addedLanes[0];
        self::assertSame('ground-id', $addedLane['archeryGroundId']);
        self::assertSame('Lane 1', $addedLane['lane']->name());
        self::assertSame(42.5, $addedLane['lane']->maxDistance());
    }
}
