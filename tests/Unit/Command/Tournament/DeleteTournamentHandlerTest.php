<?php

declare(strict_types=1);

namespace App\Tests\Unit\Command\Tournament;

use App\Application\Command\Tournament\DeleteTournament;
use App\Application\Command\Tournament\DeleteTournamentHandler;
use App\Tests\Unit\Support\InMemoryTournamentRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class DeleteTournamentHandlerTest extends TestCase
{
    public function testDeletesTournament(): void
    {
        $repository = new InMemoryTournamentRepository();
        $handler    = new DeleteTournamentHandler($repository);

        $tournamentId = Uuid::v4()->toRfc4122();

        $result = $handler(new DeleteTournament($tournamentId));

        self::assertTrue($result->success);
        self::assertSame('Tournament deleted.', $result->message);
        self::assertSame([$tournamentId], $repository->deleted);
    }
}
