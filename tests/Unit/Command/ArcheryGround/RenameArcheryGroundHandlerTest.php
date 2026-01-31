<?php

declare(strict_types=1);

namespace App\Tests\Unit\Command\ArcheryGround;

use App\Application\Command\ArcheryGround\RenameArcheryGround;
use App\Application\Command\ArcheryGround\RenameArcheryGroundHandler;
use App\Domain\Entity\ArcheryGround;
use App\Tests\Unit\Support\InMemoryArcheryGroundRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class RenameArcheryGroundHandlerTest extends TestCase
{
    public function testRenamesArcheryGround(): void
    {
        $repository = new InMemoryArcheryGroundRepository();
        $handler    = new RenameArcheryGroundHandler($repository);

        $groundId = Uuid::v4()->toRfc4122();
        $repository->seed(new ArcheryGround($groundId, 'Old Name'));

        $result = $handler(new RenameArcheryGround($groundId, 'New Name'));

        self::assertTrue($result->success);
        self::assertCount(1, $repository->saved);
        self::assertSame('New Name', $repository->saved[0]->name());
    }
}
