<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Dbal\Hydrator;

use App\Domain\Entity\ArcheryGround;
use App\Domain\Entity\Tournament;
use App\Domain\Entity\Tournament\Attachment;
use App\Domain\Entity\TournamentTargetCollection;
use App\Domain\ValueObject\Ruleset;
use DateTimeImmutable;

final class TournamentHydrator
{
    /**
     * @param array{id: string, name: string, event_date: string, ruleset: string, number_of_targets: int|string} $row
     * @param list<Attachment>                                                                                    $attachments
     */
    public function hydrate(
        array $row,
        ArcheryGround $archeryGround,
        TournamentTargetCollection $targets,
        array $attachments = [],
    ): Tournament {
        return new Tournament(
            id: $row['id'],
            name: $row['name'],
            eventDate: new DateTimeImmutable($row['event_date']),
            ruleset: Ruleset::from($row['ruleset']),
            archeryGround: $archeryGround,
            numberOfTargets: (int) $row['number_of_targets'],
            targets: $targets,
            attachments: $attachments,
        );
    }
}
