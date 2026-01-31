<?php

declare(strict_types=1);

namespace App\Application\Bus;

use App\Application\Command\CommandResult;

interface CommandBus
{
    public function dispatch(object $command): CommandResult;
}
