<?php

declare(strict_types=1);

namespace App\Tests\Unit\Command\Tournament;

use App\Application\Command\Tournament\RemoveAttachment;
use App\Application\Command\Tournament\RemoveAttachmentHandler;
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

#[CoversClass(RemoveAttachmentHandler::class)]
final class RemoveAttachmentHandlerTest extends TestCase
{
    #[Test]
    public function removesAttachmentSuccessfully(): void
    {
        $repository = new InMemoryTournamentRepository();
        $storage    = new SpyAttachmentStorage();
        $handler    = new RemoveAttachmentHandler($repository, $storage);

        $tournamentId = Uuid::v4()->toRfc4122();
        $attachmentId = Uuid::v4()->toRfc4122();

        $tournament = new Tournament(
            id: $tournamentId,
            name: 'Spring Championship',
            eventDate: new DateTimeImmutable(),
            ruleset: Ruleset::DSB_3D,
            archeryGround: ArcheryGroundSmallSized::create(),
            numberOfTargets: 28,
            targets: new TournamentTargetCollection(),
        );
        $tournament->addAttachment(new Attachment(
            id: $attachmentId,
            title: 'Tournament Overview',
            filePath: '/uploads/attachments/overview.pdf',
            mimeType: 'application/pdf',
            originalFilename: 'tournament-overview.pdf',
        ));

        $repository->seed($tournament);

        $result = $handler(new RemoveAttachment($tournamentId, $attachmentId));

        self::assertTrue($result->success);
        self::assertStringContainsString('Tournament Overview', (string) $result->message);
        self::assertSame(['/uploads/attachments/overview.pdf'], $storage->removed);
        self::assertSame([$attachmentId], $repository->removedAttachments);
    }

    #[Test]
    public function failsWhenTournamentNotFound(): void
    {
        $repository = new InMemoryTournamentRepository();
        $storage    = new SpyAttachmentStorage();
        $handler    = new RemoveAttachmentHandler($repository, $storage);

        $result = $handler(new RemoveAttachment('non-existent-id', 'attachment-id'));

        self::assertFalse($result->success);
        self::assertStringContainsString('Tournament not found', (string) $result->message);
    }

    #[Test]
    public function removesAttachmentEvenWhenNotFoundInTournament(): void
    {
        $repository = new InMemoryTournamentRepository();
        $storage    = new SpyAttachmentStorage();
        $handler    = new RemoveAttachmentHandler($repository, $storage);

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

        $result = $handler(new RemoveAttachment($tournamentId, 'non-existent-attachment'));

        self::assertTrue($result->success);
        self::assertSame([], $storage->removed);
        self::assertSame(['non-existent-attachment'], $repository->removedAttachments);
    }
}
