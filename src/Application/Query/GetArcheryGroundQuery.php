<?php

declare(strict_types=1);

namespace App\Application\Query;

use App\Domain\Entity\ArcheryGround;
use App\Domain\Repository\ArcheryGroundRepository;
use RuntimeException;

final readonly class GetArcheryGroundQuery
{
    public function __construct(private ArcheryGroundRepository $archeryGroundRepository)
    {
    }

    public function query(string $id): ArcheryGround
    {
        $archeryGround = $this->archeryGroundRepository->find($id);
        if (! $archeryGround instanceof ArcheryGround) {
            throw new RuntimeException('Archery ground not found. Create one in the UI first.');
        }

        return $archeryGround;
    }
}
