<?php

declare(strict_types=1);

namespace App\Tests\Unit\Command\Tournament;

use App\Application\Command\Tournament\DeleteTournament;
use App\Application\Command\Tournament\DeleteTournamentHandler;
use App\Domain\Entity\Tournament;
use App\Domain\Entity\TournamentTargetCollection;
use App\Domain\ValueObject\Ruleset;
use App\Tests\Fixtures\ArcheryGroundSmallSized;
use App\Tests\Unit\Support\InMemoryTournamentRepository;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class DeleteTournamentHandlerTest extends TestCase
{
    public function testDeletesTournament(): void
    {
        $repository = new InMemoryTournamentRepository();
        $handler    = new DeleteTournamentHandler($repository);

        $tournamentId = Uuid::v4()->toRfc4122();
        $tournament   = new Tournament(
            id: $tournamentId,
            name: 'Spring Championship',
            eventDate: new DateTimeImmutable(),
            ruleset: Ruleset::DSB_3D,
            archeryGround: ArcheryGroundSmallSized::create(),
            numberOfTargets: 28,
            targets: new TournamentTargetCollection(),
        );
        $repository->seed($tournament);

        $result = $handler(new DeleteTournament($tournamentId));

        self::assertTrue($result->success);
        self::assertSame('The tournament "Spring Championship" was deleted.', $result->message);
        self::assertSame([$tournamentId], $repository->deleted);
    }
}
