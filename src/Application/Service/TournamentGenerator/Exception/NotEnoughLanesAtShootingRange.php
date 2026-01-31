<?php

declare(strict_types=1);

namespace App\Application\Service\TournamentGenerator\Exception;

final class NotEnoughLanesAtShootingRange extends TournamentGenerationFailed
{
    public static function noLanesAtShootingRange(): self
    {
        return new self('The shooting range is missing any shooting lanes.');
    }

    public static function notQualifiedLanesAtShootingRange(): self
    {
        return new self('There are no qualified lanes at the shooting range for the tournament requirements.');
    }
}
