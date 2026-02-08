<?php

declare(strict_types=1);

namespace App\Tests\Unit\Command\ArcheryGround;

use App\Application\Command\ArcheryGround\UpdateTargetImage;
use App\Application\Command\ArcheryGround\UpdateTargetImageHandler;
use App\Domain\Entity\ArcheryGround;
use App\Domain\Entity\ArcheryGround\Target;
use App\Domain\ValueObject\TargetType;
use App\Tests\Unit\Support\InMemoryArcheryGroundRepository;
use App\Tests\Unit\Support\SpyTargetImageStorage;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Uid\Uuid;

final class UpdateTargetImageHandlerTest extends TestCase
{
    public function testUpdatesTargetImage(): void
    {
        $repository = new InMemoryArcheryGroundRepository();
        $storage    = new SpyTargetImageStorage();
        $handler    = new UpdateTargetImageHandler($repository, $storage);

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

        $result = $handler(new UpdateTargetImage($groundId, $targetId, $uploadedFile));

        self::assertTrue($result->success);
        self::assertSame('Target image updated.', $result->message);
        self::assertCount(1, $storage->stored);
        self::assertSame($targetId, $storage->stored[0]['targetId']);
        self::assertSame(['/uploads/targets/old-image.png'], $storage->removed);
        self::assertCount(1, $repository->updatedTargetImages);
        self::assertSame($targetId, $repository->updatedTargetImages[0]['targetId']);
    }

    public function testFailsWhenArcheryGroundNotFound(): void
    {
        $repository = new InMemoryArcheryGroundRepository();
        $storage    = new SpyTargetImageStorage();
        $handler    = new UpdateTargetImageHandler($repository, $storage);

        $uploadedFile = self::createStub(UploadedFile::class);

        $result = $handler(new UpdateTargetImage(
            Uuid::v4()->toRfc4122(),
            Uuid::v4()->toRfc4122(),
            $uploadedFile,
        ));

        self::assertFalse($result->success);
        self::assertSame('Archery ground not found.', $result->message);
    }

    public function testFailsWhenTargetNotFound(): void
    {
        $repository = new InMemoryArcheryGroundRepository();
        $storage    = new SpyTargetImageStorage();
        $handler    = new UpdateTargetImageHandler($repository, $storage);

        $groundId = Uuid::v4()->toRfc4122();
        $ground   = new ArcheryGround($groundId, 'Test Ground');
        $repository->seed($ground);

        $uploadedFile = self::createStub(UploadedFile::class);

        $result = $handler(new UpdateTargetImage(
            $groundId,
            Uuid::v4()->toRfc4122(),
            $uploadedFile,
        ));

        self::assertFalse($result->success);
        self::assertSame('Target not found.', $result->message);
    }
}
