<?php

declare(strict_types=1);

namespace App\Application\Query\ArcheryGround;

use App\Domain\Entity\ArcheryGround;
use App\Domain\Repository\ArcheryGroundRepository;

final readonly class ListArcheryGroundsHandler
{
    public function __construct(private ArcheryGroundRepository $archeryGroundRepository)
    {
    }

    /** @return list<ArcheryGround> */
    public function __invoke(ListArcheryGrounds $query): array
    {
        unset($query);

        return $this->archeryGroundRepository->findAll();
    }
}
