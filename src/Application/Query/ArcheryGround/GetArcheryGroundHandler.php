<?php

declare(strict_types=1);

namespace App\Application\Query\ArcheryGround;

use App\Domain\Entity\ArcheryGround;
use App\Domain\Repository\ArcheryGroundRepository;

final readonly class GetArcheryGroundHandler
{
    public function __construct(private ArcheryGroundRepository $archeryGroundRepository)
    {
    }

    public function __invoke(GetArcheryGround $query): ArcheryGround|null
    {
        return $this->archeryGroundRepository->find($query->id);
    }
}
