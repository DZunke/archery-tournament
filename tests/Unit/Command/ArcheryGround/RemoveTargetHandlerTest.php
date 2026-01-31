<?php

declare(strict_types=1);

namespace App\Tests\Unit\Command\ArcheryGround;

use App\Application\Command\ArcheryGround\RemoveTarget;
use App\Application\Command\ArcheryGround\RemoveTargetHandler;
use App\Domain\Entity\ArcheryGround;
use App\Domain\Entity\ArcheryGround\Target;
use App\Domain\ValueObject\TargetType;
use App\Tests\Unit\Support\InMemoryArcheryGroundRepository;
use App\Tests\Unit\Support\SpyTargetImageStorage;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class RemoveTargetHandlerTest extends TestCase
{
    public function testRemovesTargetImageAndRecord(): void
    {
        $repository = new InMemoryArcheryGroundRepository();
        $storage    = new SpyTargetImageStorage();
        $handler    = new RemoveTargetHandler($repository, $storage);

        $groundId = Uuid::v4()->toRfc4122();
        $targetId = Uuid::v4()->toRfc4122();

        $ground = new ArcheryGround($groundId, 'Main Ground');
        $ground->addTarget(new Target(
            id: $targetId,
            type: TargetType::ANIMAL_GROUP_1,
            name: 'Deer',
            image: '/uploads/targets/deer.png',
        ));

        $repository->seed($ground);

        $result = $handler(new RemoveTarget($groundId, $targetId));

        self::assertTrue($result->success);
        self::assertSame(['/uploads/targets/deer.png'], $storage->removed);
        self::assertSame([$targetId], $repository->removedTargets);
    }
}
