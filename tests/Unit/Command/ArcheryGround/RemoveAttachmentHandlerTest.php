<?php

declare(strict_types=1);

namespace App\Tests\Unit\Command\ArcheryGround;

use App\Application\Command\ArcheryGround\RemoveAttachment;
use App\Application\Command\ArcheryGround\RemoveAttachmentHandler;
use App\Domain\Entity\ArcheryGround;
use App\Domain\Entity\ArcheryGround\Attachment;
use App\Tests\Unit\Support\InMemoryArcheryGroundRepository;
use App\Tests\Unit\Support\SpyAttachmentStorage;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

#[CoversClass(RemoveAttachmentHandler::class)]
final class RemoveAttachmentHandlerTest extends TestCase
{
    #[Test]
    public function removesAttachmentFileAndRecord(): void
    {
        $repository = new InMemoryArcheryGroundRepository();
        $storage    = new SpyAttachmentStorage();
        $handler    = new RemoveAttachmentHandler($repository, $storage);

        $groundId     = Uuid::v4()->toRfc4122();
        $attachmentId = Uuid::v4()->toRfc4122();

        $ground = new ArcheryGround($groundId, 'Main Ground');
        $ground->addAttachment(new Attachment(
            id: $attachmentId,
            title: 'Ground Overview',
            filePath: '/uploads/attachments/overview.pdf',
            mimeType: 'application/pdf',
            originalFilename: 'ground-overview.pdf',
        ));

        $repository->seed($ground);

        $result = $handler(new RemoveAttachment($groundId, $attachmentId));

        self::assertTrue($result->success);
        self::assertStringContainsString('Ground Overview', (string) $result->message);
        self::assertSame(['/uploads/attachments/overview.pdf'], $storage->removed);
        self::assertSame([$attachmentId], $repository->removedAttachments);
    }

    #[Test]
    public function failsWhenArcheryGroundNotFound(): void
    {
        $repository = new InMemoryArcheryGroundRepository();
        $storage    = new SpyAttachmentStorage();
        $handler    = new RemoveAttachmentHandler($repository, $storage);

        $result = $handler(new RemoveAttachment('non-existent-ground', 'attachment-id'));

        self::assertFalse($result->success);
        self::assertSame('Archery ground not found.', $result->message);
        self::assertCount(0, $storage->removed);
        self::assertCount(0, $repository->removedAttachments);
    }

    #[Test]
    public function handlesUnknownAttachmentGracefully(): void
    {
        $repository = new InMemoryArcheryGroundRepository();
        $storage    = new SpyAttachmentStorage();
        $handler    = new RemoveAttachmentHandler($repository, $storage);

        $groundId = Uuid::v4()->toRfc4122();
        $ground   = new ArcheryGround($groundId, 'Main Ground');
        $repository->seed($ground);

        $result = $handler(new RemoveAttachment($groundId, 'unknown-attachment-id'));

        // The handler still removes from repository even if attachment is not found in memory
        // This is consistent with the RemoveTargetHandler behavior
        self::assertTrue($result->success);
        self::assertStringContainsString('Unknown', (string) $result->message);
    }
}
