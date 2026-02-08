<?php

declare(strict_types=1);

namespace App\Tests\Unit\Command\Tournament;

use App\Application\Command\Tournament\DeleteTournament;
use App\Application\Command\Tournament\DeleteTournamentHandler;
use App\Domain\Entity\Tournament;
use App\Domain\Entity\Tournament\Attachment;
use App\Domain\Entity\TournamentTargetCollection;
use App\Domain\ValueObject\Ruleset;
use App\Tests\Fixtures\ArcheryGroundSmallSized;
use App\Tests\Unit\Support\InMemoryTournamentRepository;
use App\Tests\Unit\Support\SpyAttachmentStorage;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

#[CoversClass(DeleteTournamentHandler::class)]
final class DeleteTournamentHandlerTest extends TestCase
{
    #[Test]
    public function deletesTournament(): void
    {
        $repository        = new InMemoryTournamentRepository();
        $attachmentStorage = new SpyAttachmentStorage();
        $handler           = new DeleteTournamentHandler($repository, $attachmentStorage);

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

    #[Test]
    public function deletesTournamentWithAttachments(): void
    {
        $repository        = new InMemoryTournamentRepository();
        $attachmentStorage = new SpyAttachmentStorage();
        $handler           = new DeleteTournamentHandler($repository, $attachmentStorage);

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
        $tournament->addAttachment(new Attachment(
            id: Uuid::v4()->toRfc4122(),
            title: 'Tournament Rules',
            filePath: '/uploads/attachments/rules.pdf',
            mimeType: 'application/pdf',
            originalFilename: 'tournament-rules.pdf',
        ));
        $repository->seed($tournament);

        $result = $handler(new DeleteTournament($tournamentId));

        self::assertTrue($result->success);
        self::assertSame(['/uploads/attachments/rules.pdf'], $attachmentStorage->removed);
        self::assertSame([$tournamentId], $repository->deleted);
    }
}
