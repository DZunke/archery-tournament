<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\Entity\Tournament\Attachment;
use App\Domain\ValueObject\Ruleset;
use DateTimeImmutable;
use Symfony\Component\Uid\Uuid;
use Webmozart\Assert\Assert;

final class Tournament
{
    /** @param list<Attachment> $attachments */
    public function __construct(
        private readonly string $id,
        private readonly string $name,
        private readonly DateTimeImmutable $eventDate,
        private readonly Ruleset $ruleset,
        private readonly ArcheryGround $archeryGround,
        private readonly int $numberOfTargets,
        private TournamentTargetCollection $targets,
        private array $attachments = [],
    ) {
        Assert::uuid($this->id, 'The tournament id must be a valid UUID.');
        Assert::notEmpty($this->name, 'The tournament name must not be empty.');
        Assert::greaterThan($this->numberOfTargets, 0, 'The number of targets must be greater than zero.');
    }

    public static function create(
        string $name,
        Ruleset $ruleset,
        ArcheryGround $archeryGround,
        int $numberOfTargets,
    ): self {
        return new self(
            id: Uuid::v4()->toRfc4122(),
            name: $name,
            eventDate: new DateTimeImmutable(),
            ruleset: $ruleset,
            archeryGround: $archeryGround,
            numberOfTargets: $numberOfTargets,
            targets: new TournamentTargetCollection(),
        );
    }

    public function id(): string
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function eventDate(): DateTimeImmutable
    {
        return $this->eventDate;
    }

    public function ruleset(): Ruleset
    {
        return $this->ruleset;
    }

    public function archeryGround(): ArcheryGround
    {
        return $this->archeryGround;
    }

    public function numberOfTargets(): int
    {
        return $this->numberOfTargets;
    }

    public function targets(): TournamentTargetCollection
    {
        return $this->targets;
    }

    public function replaceTargets(TournamentTargetCollection $targets): void
    {
        $this->targets = $targets;
    }

    public function addTarget(TournamentTarget $target): void
    {
        $this->targets->add($target);
    }

    /** @return list<Attachment> */
    public function attachments(): array
    {
        return $this->attachments;
    }

    public function addAttachment(Attachment $attachment): void
    {
        $this->attachments[] = $attachment;
    }
}
