<?php

declare(strict_types=1);

namespace App\Presentation\Input\Tournament;

use App\Application\Command\Tournament\CreateTournament;
use App\Domain\ValueObject\Ruleset;
use DateTimeImmutable;
use Symfony\Component\HttpFoundation\Request;

use function ctype_digit;
use function in_array;
use function trim;

final readonly class CreateTournamentInput
{
    public function __construct(
        public string $archeryGroundId,
        public string $name,
        public string $eventDate,
        public string $ruleset,
        public string $numberOfTargets,
        public string $mode,
        public bool $randomizeStakesBetweenRounds,
        public bool $includeTrainingOnly,
    ) {
    }

    public static function fromRequest(Request $request): self
    {
        return new self(
            (string) $request->request->get('archery_ground_id', ''),
            (string) $request->request->get('name', ''),
            (string) $request->request->get('event_date', ''),
            (string) $request->request->get('ruleset', ''),
            (string) $request->request->get('number_of_targets', ''),
            (string) $request->request->get('mode', 'auto'),
            $request->request->getBoolean('randomize_stakes_between_rounds'),
            $request->request->getBoolean('include_training_only'),
        );
    }

    /** @return list<string> */
    public function errors(): array
    {
        $errors = [];

        if (trim($this->name) === '') {
            $errors[] = 'Tournament name is required.';
        }

        if (trim($this->archeryGroundId) === '') {
            $errors[] = 'Archery ground is required.';
        }

        if (Ruleset::tryFrom($this->ruleset) === null) {
            $errors[] = 'Ruleset is invalid.';
        }

        if (! ctype_digit($this->numberOfTargets) || (int) $this->numberOfTargets <= 0) {
            $errors[] = 'Number of targets must be greater than zero.';
        }

        if (! in_array($this->mode, ['auto', 'manual'], true)) {
            $errors[] = 'Tournament mode is invalid.';
        }

        if ($this->eventDate !== '' && DateTimeImmutable::createFromFormat('Y-m-d', $this->eventDate) === false) {
            $errors[] = 'Event date must be a valid date.';
        }

        return $errors;
    }

    public function toCommand(): CreateTournament
    {
        $ruleset = Ruleset::from($this->ruleset);
        $date    = $this->eventDate !== ''
            ? new DateTimeImmutable($this->eventDate)
            : new DateTimeImmutable();

        return new CreateTournament(
            archeryGroundId: $this->archeryGroundId,
            name: trim($this->name),
            eventDate: $date,
            ruleset: $ruleset,
            numberOfTargets: (int) $this->numberOfTargets,
            autoGenerate: $this->mode === 'auto',
            randomizeStakesBetweenRounds: $this->randomizeStakesBetweenRounds,
            includeTrainingOnly: $this->includeTrainingOnly,
        );
    }
}
