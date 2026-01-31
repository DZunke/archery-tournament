<?php

declare(strict_types=1);

namespace App\Application\Query\ArcheryGround;

final readonly class GetArcheryGround
{
    public function __construct(public string $id)
    {
    }
}
