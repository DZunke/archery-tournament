<?php

declare(strict_types=1);

namespace App\Tests\Unit\Command\ArcheryGround;

use App\Application\Command\ArcheryGround\CreateArcheryGround;
use App\Application\Command\ArcheryGround\CreateArcheryGroundHandler;
use App\Tests\Unit\Support\InMemoryArcheryGroundRepository;
use PHPUnit\Framework\TestCase;

final class CreateArcheryGroundHandlerTest extends TestCase
{
    public function testCreatesArcheryGround(): void
    {
        $repository = new InMemoryArcheryGroundRepository();
        $handler    = new CreateArcheryGroundHandler($repository);

        $result = $handler(new CreateArcheryGround('Forest Range'));

        self::assertTrue($result->success);
        self::assertCount(1, $repository->saved);
        self::assertSame('Forest Range', $repository->saved[0]->name());
    }
}
