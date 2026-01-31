<?php

declare(strict_types=1);

namespace App\Application\Command\ArcheryGround;

use App\Application\Command\CommandResult;
use App\Domain\Entity\ArcheryGround;
use App\Domain\Repository\ArcheryGroundRepository;

use function trim;

final readonly class CreateArcheryGroundHandler
{
    public function __construct(private ArcheryGroundRepository $archeryGroundRepository)
    {
    }

    public function __invoke(CreateArcheryGround $command): CommandResult
    {
        $name = trim($command->name);
        if ($name === '') {
            return CommandResult::failure('Please provide a name for the archery ground.');
        }

        $archeryGround = new ArcheryGround(
            id: $this->archeryGroundRepository->nextIdentity(),
            name: $name,
        );

        $this->archeryGroundRepository->save($archeryGround);

        return CommandResult::success('Archery ground created.', ['id' => $archeryGround->id()]);
    }
}
