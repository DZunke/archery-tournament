<?php

declare(strict_types=1);

namespace App\Application\Command\ArcheryGround;

final readonly class DeleteArcheryGround
{
    public function __construct(public string $id)
    {
    }
}
