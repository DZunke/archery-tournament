<?php

declare(strict_types=1);

namespace App\Application\Command\ArcheryGround;

final readonly class CreateArcheryGround
{
    public function __construct(public string $name)
    {
    }
}
