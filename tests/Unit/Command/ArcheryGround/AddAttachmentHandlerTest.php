<?php

declare(strict_types=1);

namespace App\Tests\Unit\Command\ArcheryGround;

use App\Application\Command\ArcheryGround\AddAttachment;
use App\Application\Command\ArcheryGround\AddAttachmentHandler;
use App\Tests\Unit\Support\InMemoryArcheryGroundRepository;
use App\Tests\Unit\Support\SpyAttachmentStorage;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

use function base64_decode;
use function bin2hex;
use function file_put_contents;
use function is_file;
use function random_bytes;
use function sys_get_temp_dir;
use function unlink;

#[CoversClass(AddAttachmentHandler::class)]
final class AddAttachmentHandlerTest extends TestCase
{
    private const string PLACEHOLDER_PDF_HEADER = '%PDF-1.4';

    #[Test]
    public function addsAttachmentSuccessfully(): void
    {
        $repository = new InMemoryArcheryGroundRepository();
        $storage    = new SpyAttachmentStorage();
        $handler    = new AddAttachmentHandler($repository, $storage);

        $file    = $this->createUploadedPdf();
        $command = new AddAttachment(
            archeryGroundId: 'ground-id',
            title: 'Ground Overview',
            file: $file,
        );

        $result = $handler($command);

        self::assertTrue($result->success);
        self::assertStringContainsString('Ground Overview', (string) $result->message);
        self::assertCount(1, $repository->addedAttachments);
        self::assertCount(1, $storage->stored);

        $addedAttachment = $repository->addedAttachments[0]['attachment'];
        self::assertSame('Ground Overview', $addedAttachment->title());
        self::assertSame($storage->stored[0]['path'], $addedAttachment->filePath());

        $this->cleanupFile($file);
    }

    #[Test]
    public function addsImageAttachmentSuccessfully(): void
    {
        $repository = new InMemoryArcheryGroundRepository();
        $storage    = new SpyAttachmentStorage();
        $handler    = new AddAttachmentHandler($repository, $storage);

        $file    = $this->createUploadedImage();
        $command = new AddAttachment(
            archeryGroundId: 'ground-id',
            title: 'Lane Photo',
            file: $file,
        );

        $result = $handler($command);

        self::assertTrue($result->success);
        self::assertCount(1, $repository->addedAttachments);

        $addedAttachment = $repository->addedAttachments[0]['attachment'];
        self::assertSame('Lane Photo', $addedAttachment->title());

        $this->cleanupFile($file);
    }

    private function createUploadedPdf(): UploadedFile
    {
        $path = sys_get_temp_dir() . '/' . bin2hex(random_bytes(8)) . '.pdf';
        file_put_contents($path, self::PLACEHOLDER_PDF_HEADER . "\nPlaceholder PDF content");

        return new UploadedFile($path, 'document.pdf', 'application/pdf', null, true);
    }

    private function createUploadedImage(): UploadedFile
    {
        $placeholderImage = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR4nGMAAQAABQABDQottAAAAABJRU5ErkJggg==';
        $path             = sys_get_temp_dir() . '/' . bin2hex(random_bytes(8)) . '.png';
        file_put_contents($path, base64_decode($placeholderImage, true));

        return new UploadedFile($path, 'image.png', 'image/png', null, true);
    }

    private function cleanupFile(UploadedFile $file): void
    {
        if (! is_file($file->getPathname())) {
            return;
        }

        unlink($file->getPathname());
    }
}
