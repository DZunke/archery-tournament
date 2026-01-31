<?php

declare(strict_types=1);

namespace App\Tests\Unit\Command\ArcheryGround;

use App\Application\Command\ArcheryGround\AddTarget;
use App\Application\Command\ArcheryGround\AddTargetHandler;
use App\Domain\ValueObject\TargetType;
use App\Tests\Unit\Support\InMemoryArcheryGroundRepository;
use App\Tests\Unit\Support\SpyTargetImageStorage;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

use function base64_decode;
use function bin2hex;
use function file_put_contents;
use function is_file;
use function random_bytes;
use function sys_get_temp_dir;
use function unlink;

final class AddTargetHandlerTest extends TestCase
{
    private const string PLACEHOLDER_IMAGE = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR4nGMAAQAABQABDQottAAAAABJRU5ErkJggg==';

    public function testAddsTarget(): void
    {
        $repository = new InMemoryArcheryGroundRepository();
        $storage    = new SpyTargetImageStorage();
        $handler    = new AddTargetHandler($repository, $storage);

        $file    = $this->createUploadedImage();
        $command = new AddTarget(
            'ground-id',
            TargetType::ANIMAL_GROUP_1,
            'Deer',
            $file,
        );

        $result = $handler($command);

        self::assertTrue($result->success);
        self::assertCount(1, $repository->addedTargets);
        self::assertCount(1, $storage->stored);

        $addedTarget = $repository->addedTargets[0]['target'];
        self::assertSame('Deer', $addedTarget->name());
        self::assertSame(TargetType::ANIMAL_GROUP_1, $addedTarget->type());
        self::assertSame($storage->stored[0]['path'], $addedTarget->image());

        if (! is_file($file->getPathname())) {
            return;
        }

        unlink($file->getPathname());
    }

    private function createUploadedImage(): UploadedFile
    {
        $path = sys_get_temp_dir() . '/target_' . bin2hex(random_bytes(6)) . '.png';
        file_put_contents($path, $this->decodePlaceholderImage());

        return new UploadedFile($path, 'target.png', 'image/png', null, true);
    }

    private function decodePlaceholderImage(): string
    {
        $data = base64_decode(self::PLACEHOLDER_IMAGE, true);
        if ($data === false) {
            self::fail('Failed to decode placeholder image.');
        }

        return $data;
    }
}
