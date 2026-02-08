<?php

declare(strict_types=1);

namespace App\Tests\Unit\Command\ArcheryGround;

use App\Application\Command\ArcheryGround\DeleteArcheryGround;
use App\Application\Command\ArcheryGround\DeleteArcheryGroundHandler;
use App\Domain\Entity\ArcheryGround;
use App\Domain\Entity\ArcheryGround\Attachment;
use App\Domain\Entity\ArcheryGround\Target;
use App\Domain\ValueObject\TargetType;
use App\Tests\Unit\Support\InMemoryArcheryGroundRepository;
use App\Tests\Unit\Support\SpyAttachmentStorage;
use App\Tests\Unit\Support\SpyTargetImageStorage;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

#[CoversClass(DeleteArcheryGroundHandler::class)]
final class DeleteArcheryGroundHandlerTest extends TestCase
{
    #[Test]
    public function deletesArcheryGroundAndCleansUpImages(): void
    {
        $repository        = new InMemoryArcheryGroundRepository();
        $targetStorage     = new SpyTargetImageStorage();
        $attachmentStorage = new SpyAttachmentStorage();
        $handler           = new DeleteArcheryGroundHandler($repository, $targetStorage, $attachmentStorage);

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
        $ground->addAttachment(new Attachment(
            id: Uuid::v4()->toRfc4122(),
            title: 'Ground Map',
            filePath: '/uploads/attachments/map.pdf',
            mimeType: 'application/pdf',
            originalFilename: 'ground-map.pdf',
        ));

        $repository->seed($ground);

        $result = $handler(new DeleteArcheryGround($groundId));

        self::assertTrue($result->success);
        self::assertSame(['/uploads/targets/deer.png', '/uploads/targets/fox.png'], $targetStorage->removed);
        self::assertSame(['/uploads/attachments/map.pdf'], $attachmentStorage->removed);
        self::assertSame([$groundId], $repository->deleted);
    }
}
