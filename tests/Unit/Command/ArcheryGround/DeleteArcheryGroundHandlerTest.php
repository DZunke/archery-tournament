<?php

declare(strict_types=1);

namespace App\Tests\Unit\Command\ArcheryGround;

use App\Application\Command\ArcheryGround\DeleteArcheryGround;
use App\Application\Command\ArcheryGround\DeleteArcheryGroundHandler;
use App\Domain\Entity\ArcheryGround;
use App\Domain\Entity\ArcheryGround\Target;
use App\Domain\ValueObject\TargetType;
use App\Tests\Unit\Support\InMemoryArcheryGroundRepository;
use App\Tests\Unit\Support\SpyTargetImageStorage;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class DeleteArcheryGroundHandlerTest extends TestCase
{
    public function testDeletesArcheryGroundAndCleansUpImages(): void
    {
        $repository = new InMemoryArcheryGroundRepository();
        $storage    = new SpyTargetImageStorage();
        $handler    = new DeleteArcheryGroundHandler($repository, $storage);

        $groundId = Uuid::v4()->toRfc4122();

        $ground = new ArcheryGround($groundId, 'Forest Course');
        $ground->addTarget(new Target(
            id: Uuid::v4()->toRfc4122(),
            type: TargetType::ANIMAL_GROUP_1,
            name: 'Deer',
            image: '/uploads/targets/deer.png',
        ));
        $ground->addTarget(new Target(
            id: Uuid::v4()->toRfc4122(),
            type: TargetType::ANIMAL_GROUP_2,
            name: 'Fox',
            image: '/uploads/targets/fox.png',
        ));

        $repository->seed($ground);

        $result = $handler(new DeleteArcheryGround($groundId));

        self::assertTrue($result->success);
        self::assertSame(['/uploads/targets/deer.png', '/uploads/targets/fox.png'], $storage->removed);
        self::assertSame([$groundId], $repository->deleted);
    }
}
