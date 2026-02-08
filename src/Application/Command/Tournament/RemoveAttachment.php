<?php

declare(strict_types=1);

namespace App\Application\Command\Tournament;

final readonly class RemoveAttachment
{
    public function __construct(
        public string $tournamentId,
        public string $attachmentId,
    ) {
    }
}
