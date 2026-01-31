<?php

declare(strict_types=1);

namespace App\Application\Command\ArcheryGround;

use App\Application\Command\CommandResult;
use App\Application\Service\TargetImageStorage;
use App\Domain\Entity\ArcheryGround\Target;
use App\Domain\Repository\ArcheryGroundRepository;
use App\Domain\ValueObject\TargetType;
use RuntimeException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use ValueError;

use function trim;

final readonly class AddTargetHandler
{
    public function __construct(
        private ArcheryGroundRepository $archeryGroundRepository,
        private TargetImageStorage $targetImageStorage,
    ) {
    }

    public function __invoke(AddTarget $command): CommandResult
    {
        $name = trim($command->name);
        if ($name === '') {
            return CommandResult::failure('Target name is required.');
        }

        try {
            $type = TargetType::from($command->type);
        } catch (ValueError) {
            return CommandResult::failure('Target type is invalid.');
        }

        if (! $command->image instanceof UploadedFile) {
            return CommandResult::failure('Please upload an image for the target.');
        }

        $targetId = $this->archeryGroundRepository->nextIdentity();

        try {
            $imagePath = $this->targetImageStorage->store($command->image, $targetId);
        } catch (RuntimeException $exception) {
            return CommandResult::failure($exception->getMessage());
        }

        $target = new Target(
            id: $targetId,
            type: $type,
            name: $name,
            image: $imagePath,
        );

        $this->archeryGroundRepository->addTarget($command->archeryGroundId, $target);

        return CommandResult::success('Target added.');
    }
}
