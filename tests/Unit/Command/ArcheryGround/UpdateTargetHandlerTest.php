<?php

declare(strict_types=1);

namespace App\Tests\Unit\Command\ArcheryGround;

use App\Application\Command\ArcheryGround\UpdateTarget;
use App\Application\Command\ArcheryGround\UpdateTargetHandler;
use App\Domain\Entity\ArcheryGround;
use App\Domain\Entity\ArcheryGround\Target;
use App\Domain\ValueObject\TargetType;
use App\Tests\Unit\Support\InMemoryArcheryGroundRepository;
use App\Tests\Unit\Support\SpyTargetImageStorage;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Uid\Uuid;

#[CoversClass(UpdateTargetHandler::class)]
final class UpdateTargetHandlerTest extends TestCase
{
    #[Test]
    public function updatesTargetWithNewImage(): void
    {
        $repository = new InMemoryArcheryGroundRepository();
        $storage    = new SpyTargetImageStorage();
        $handler    = new UpdateTargetHandler($repository, $storage);

        $groundId = Uuid::v4()->toRfc4122();
        $targetId = Uuid::v4()->toRfc4122();

        $ground = new ArcheryGround($groundId, 'Test Ground');
        $ground->addTarget(new Target(
            id: $targetId,
            type: TargetType::ANIMAL_GROUP_1,
            name: 'Deer',
            image: '/uploads/targets/old-image.png',
        ));
        $repository->seed($ground);

        $uploadedFile = self::createStub(UploadedFile::class);

        $result = $handler(new UpdateTarget(
            archeryGroundId: $groundId,
            targetId: $targetId,
            name: 'Updated Deer',
            type: TargetType::ANIMAL_GROUP_2,
            image: $uploadedFile,
        ));

        self::assertTrue($result->success);
        self::assertSame('Target "Updated Deer" was updated successfully.', $result->message);
        self::assertCount(1, $storage->stored);
        self::assertSame($targetId, $storage->stored[0]['targetId']);
        self::assertSame(['/uploads/targets/old-image.png'], $storage->removed);
        self::assertCount(1, $repository->updatedTargets);
        self::assertSame($groundId, $repository->updatedTargets[0]['archeryGroundId']);
        self::assertSame($targetId, $repository->updatedTargets[0]['targetId']);
        self::assertSame('Updated Deer', $repository->updatedTargets[0]['name']);
        self::assertSame('animal_group_2', $repository->updatedTargets[0]['type']);
        self::assertNull($repository->updatedTargets[0]['targetZoneSize']);
    }

    #[Test]
    public function updatesTargetWithZoneSize(): void
    {
        $repository = new InMemoryArcheryGroundRepository();
        $storage    = new SpyTargetImageStorage();
        $handler    = new UpdateTargetHandler($repository, $storage);

        $groundId = Uuid::v4()->toRfc4122();
        $targetId = Uuid::v4()->toRfc4122();

        $ground = new ArcheryGround($groundId, 'Test Ground');
        $ground->addTarget(new Target(
            id: $targetId,
            type: TargetType::ANIMAL_GROUP_1,
            name: 'Deer',
            image: '/uploads/targets/existing-image.png',
        ));
        $repository->seed($ground);

        $result = $handler(new UpdateTarget(
            archeryGroundId: $groundId,
            targetId: $targetId,
            name: 'Updated Deer',
            type: TargetType::ANIMAL_GROUP_1, // Will be overridden by zone size
            targetZoneSize: 175, // This should derive ANIMAL_GROUP_3
        ));

        self::assertTrue($result->success);
        self::assertCount(1, $repository->updatedTargets);
        self::assertSame('Updated Deer', $repository->updatedTargets[0]['name']);
        self::assertSame('animal_group_3', $repository->updatedTargets[0]['type']);
        self::assertSame(175, $repository->updatedTargets[0]['targetZoneSize']);
    }

    #[Test]
    public function updatesTargetWithoutNewImage(): void
    {
        $repository = new InMemoryArcheryGroundRepository();
        $storage    = new SpyTargetImageStorage();
        $handler    = new UpdateTargetHandler($repository, $storage);

        $groundId = Uuid::v4()->toRfc4122();
        $targetId = Uuid::v4()->toRfc4122();

        $ground = new ArcheryGround($groundId, 'Test Ground');
        $ground->addTarget(new Target(
            id: $targetId,
            type: TargetType::ANIMAL_GROUP_1,
            name: 'Deer',
            image: '/uploads/targets/existing-image.png',
        ));
        $repository->seed($ground);

        $result = $handler(new UpdateTarget(
            archeryGroundId: $groundId,
            targetId: $targetId,
            name: 'Renamed Deer',
            type: TargetType::ANIMAL_GROUP_3,
        ));

        self::assertTrue($result->success);
        self::assertSame('Target "Renamed Deer" was updated successfully.', $result->message);
        self::assertCount(0, $storage->stored);
        self::assertEmpty($storage->removed);
        self::assertCount(1, $repository->updatedTargets);
        self::assertSame('Renamed Deer', $repository->updatedTargets[0]['name']);
        self::assertSame('animal_group_3', $repository->updatedTargets[0]['type']);
        self::assertNull($repository->updatedTargets[0]['imagePath']);
        self::assertNull($repository->updatedTargets[0]['targetZoneSize']);
    }

    #[Test]
    public function failsWhenArcheryGroundNotFound(): void
    {
        $repository = new InMemoryArcheryGroundRepository();
        $storage    = new SpyTargetImageStorage();
        $handler    = new UpdateTargetHandler($repository, $storage);

        $result = $handler(new UpdateTarget(
            archeryGroundId: Uuid::v4()->toRfc4122(),
            targetId: Uuid::v4()->toRfc4122(),
            name: 'Test',
            type: TargetType::ANIMAL_GROUP_1,
        ));

        self::assertFalse($result->success);
        self::assertSame('Archery ground not found.', $result->message);
    }

    #[Test]
    public function failsWhenTargetNotFound(): void
    {
        $repository = new InMemoryArcheryGroundRepository();
        $storage    = new SpyTargetImageStorage();
        $handler    = new UpdateTargetHandler($repository, $storage);

        $groundId = Uuid::v4()->toRfc4122();
        $ground   = new ArcheryGround($groundId, 'Test Ground');
        $repository->seed($ground);

        $result = $handler(new UpdateTarget(
            archeryGroundId: $groundId,
            targetId: Uuid::v4()->toRfc4122(),
            name: 'Test',
            type: TargetType::ANIMAL_GROUP_1,
        ));

        self::assertFalse($result->success);
        self::assertSame('Target not found.', $result->message);
    }
}
