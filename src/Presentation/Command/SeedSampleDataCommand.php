<?php

declare(strict_types=1);

namespace App\Presentation\Command;

use App\Application\Service\TargetImageStorage;
use App\Domain\Entity\ArcheryGround\Target;
use App\Domain\Repository\ArcheryGroundRepository;
use App\Infrastructure\Persistence\DatabaseMigrator;
use App\Tests\Fixtures\AcheryGroundMediumSized;
use App\Tests\Fixtures\ArcheryGroundSmallSized;
use RuntimeException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpFoundation\File\UploadedFile;

use function base64_decode;
use function bin2hex;
use function class_exists;
use function file_put_contents;
use function random_bytes;
use function sys_get_temp_dir;

#[AsCommand(
    name: 'app:db:seed',
    description: 'Seeds the database with sample archery grounds, lanes, and targets.',
)]
final class SeedSampleDataCommand extends Command
{
    private const string PLACEHOLDER_IMAGE = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR4nGMAAQAABQABDQottAAAAABJRU5ErkJggg==';

    public function __construct(
        private readonly ArcheryGroundRepository $archeryGroundRepository,
        private readonly TargetImageStorage $targetImageStorage,
        private readonly DatabaseMigrator $databaseMigrator,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('reset', 'r', InputOption::VALUE_NONE, 'Reset the database before seeding.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io          = new SymfonyStyle($input, $output);
        $shouldReset = (bool) $input->getOption('reset');

        if (! class_exists(AcheryGroundMediumSized::class) || ! class_exists(ArcheryGroundSmallSized::class)) {
            $io->error('Fixture classes are missing. Install dev dependencies to seed sample data.');

            return Command::FAILURE;
        }

        if ($shouldReset) {
            $this->databaseMigrator->reset();
            $io->note('Database reset before seeding.');
        } else {
            $this->databaseMigrator->migrate('latest');
        }

        $fixtures = [
            ArcheryGroundSmallSized::create(),
            AcheryGroundMediumSized::create(),
        ];

        foreach ($fixtures as $fixture) {
            $this->archeryGroundRepository->save($fixture);

            foreach ($fixture->shootingLanes() as $lane) {
                $this->archeryGroundRepository->addShootingLane($fixture->id(), $lane);
            }

            foreach ($fixture->targetStorage() as $target) {
                $imagePath = $this->targetImageStorage->store(
                    $this->createPlaceholderUpload(),
                    $target->id(),
                );

                $this->archeryGroundRepository->addTarget(
                    $fixture->id(),
                    new Target(
                        id: $target->id(),
                        type: $target->type(),
                        name: $target->name(),
                        image: $imagePath,
                    ),
                );
            }
        }

        $io->success('Sample data seeded.');

        return Command::SUCCESS;
    }

    private function createPlaceholderUpload(): UploadedFile
    {
        $path = sys_get_temp_dir() . '/target_' . bin2hex(random_bytes(6)) . '.png';
        file_put_contents($path, $this->decodePlaceholderImage());

        return new UploadedFile($path, 'target.png', 'image/png', null, true);
    }

    private function decodePlaceholderImage(): string
    {
        $data = base64_decode(self::PLACEHOLDER_IMAGE, true);
        if ($data === false) {
            throw new RuntimeException('Failed to decode placeholder image.');
        }

        return $data;
    }
}
