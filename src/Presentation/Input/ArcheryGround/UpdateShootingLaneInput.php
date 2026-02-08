<?php

declare(strict_types=1);

namespace App\Presentation\Input\ArcheryGround;

use App\Application\Command\ArcheryGround\UpdateShootingLane;
use Symfony\Component\HttpFoundation\Request;

use function is_numeric;
use function trim;

final readonly class UpdateShootingLaneInput
{
    public function __construct(
        public string $name,
        public string $maxDistance,
        public bool $forTrainingOnly,
        public string $notes,
    ) {
    }

    public static function fromRequest(Request $request): self
    {
        return new self(
            (string) $request->request->get('name', ''),
            (string) $request->request->get('max_distance', ''),
            $request->request->getBoolean('for_training_only'),
            (string) $request->request->get('notes', ''),
        );
    }

    /** @return list<string> */
    public function errors(): array
    {
        $errors = [];

        if (trim($this->name) === '') {
            $errors[] = 'Lane name is required.';
        }

        if ($this->maxDistance === '' || ! is_numeric($this->maxDistance)) {
            $errors[] = 'Max distance must be a number.';
        } elseif ((float) $this->maxDistance <= 0) {
            $errors[] = 'Max distance must be greater than zero.';
        }

        return $errors;
    }

    public function toCommand(string $archeryGroundId, string $laneId): UpdateShootingLane
    {
        return new UpdateShootingLane(
            archeryGroundId: $archeryGroundId,
            laneId: $laneId,
            name: trim($this->name),
            maxDistance: (float) $this->maxDistance,
            forTrainingOnly: $this->forTrainingOnly,
            notes: trim($this->notes),
        );
    }
}
