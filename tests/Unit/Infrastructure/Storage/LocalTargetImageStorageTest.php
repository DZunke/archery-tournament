<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Storage;

use App\Infrastructure\Storage\LocalTargetImageStorage;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;

use function base64_decode;
use function bin2hex;
use function file_put_contents;
use function random_bytes;
use function sys_get_temp_dir;

final class LocalTargetImageStorageTest extends TestCase
{
    private const string PLACEHOLDER_IMAGE = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR4nGMAAQAABQABDQottAAAAABJRU5ErkJggg==';

    private string $projectDir;
    private Filesystem $filesystem;

    protected function setUp(): void
    {
        $this->projectDir = sys_get_temp_dir() . '/archery-storage-' . bin2hex(random_bytes(4));
        $this->filesystem = new Filesystem();
        $this->filesystem->mkdir($this->projectDir . '/public');
    }

    protected function tearDown(): void
    {
        $this->filesystem->remove($this->projectDir);
    }

    public function testStoresAndRemovesImages(): void
    {
        $storage = new LocalTargetImageStorage($this->projectDir, $this->filesystem);
        $file    = $this->createUploadedImage();

        $path = $storage->store($file, 'target-id');

        self::assertStringStartsWith('/uploads/targets/', $path);
        self::assertFileExists($this->projectDir . '/public' . $path);

        $storage->remove($path);

        self::assertFileDoesNotExist($this->projectDir . '/public' . $path);
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
