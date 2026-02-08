<?php

declare(strict_types=1);

namespace App\Tests\Unit\Support;

use App\Domain\Entity\Tournament;
use App\Domain\Entity\Tournament\Attachment;
use App\Domain\Entity\TournamentTargetCollection;
use App\Domain\Repository\TournamentRepository;
use Symfony\Component\Uid\Uuid;

use function array_filter;
use function array_values;

final class InMemoryTournamentRepository implements TournamentRepository
{
    /** @var array<string, Tournament> */
    private array $tournaments = [];

    /** @var list<Tournament> */
    public array $saved = [];

    /** @var list<string> */
    public array $deleted = [];

    /** @var list<array{tournamentId: string, targets: TournamentTargetCollection}> */
    public array $replacedTargets = [];

    /** @var list<array{tournamentId: string, attachment: Attachment}> */
    public array $addedAttachments = [];

    /** @var list<string> */
    public array $removedAttachments = [];

    public function nextIdentity(): string
    {
        return Uuid::v4()->toRfc4122();
    }

    public function save(Tournament $tournament): void
    {
        $this->tournaments[$tournament->id()] = $tournament;
        $this->saved[]                        = $tournament;
    }

    public function find(string $id): Tournament|null
    {
        return $this->tournaments[$id] ?? null;
    }

    /** @return list<Tournament> */
    public function findAll(): array
    {
        return array_values($this->tournaments);
    }

    /** @return list<Tournament> */
    public function findByArcheryGround(string $archeryGroundId): array
    {
        return array_values(array_filter(
            $this->tournaments,
            static fn (Tournament $t) => $t->archeryGround()->id() === $archeryGroundId,
        ));
    }

    public function delete(string $id): void
    {
        $this->deleted[] = $id;
        unset($this->tournaments[$id]);
    }

    public function replaceTargets(string $tournamentId, TournamentTargetCollection $targets): void
    {
        $this->replacedTargets[] = [
            'tournamentId' => $tournamentId,
            'targets' => $targets,
        ];
    }

    public function addAttachment(string $tournamentId, Attachment $attachment): void
    {
        $this->addedAttachments[] = [
            'tournamentId' => $tournamentId,
            'attachment' => $attachment,
        ];
    }

    public function removeAttachment(string $attachmentId): void
    {
        $this->removedAttachments[] = $attachmentId;
    }

    public function seed(Tournament $tournament): void
    {
        $this->tournaments[$tournament->id()] = $tournament;
    }
}
