<?php

declare(strict_types=1);

namespace App\Application\Bus;

interface QueryBus
{
    public function ask(object $query): mixed;
}
