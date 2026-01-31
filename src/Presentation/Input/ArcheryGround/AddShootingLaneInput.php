<?php

declare(strict_types=1);

namespace App\Presentation\Input\ArcheryGround;

use App\Application\Command\ArcheryGround\AddShootingLane;
use Symfony\Component\HttpFoundation\Request;

use function is_numeric;
use function trim;

final readonly class AddShootingLaneInput
{
    public function __construct(
        public string $name,
        public string $maxDistance,
    ) {
    }

    public static function fromRequest(Request $request): self
    {
        return new self(
            (string) $request->request->get('name', ''),
            (string) $request->request->get('max_distance', ''),
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

    public function toCommand(string $archeryGroundId): AddShootingLane
    {
        return new AddShootingLane(
            $archeryGroundId,
            trim($this->name),
            (float) $this->maxDistance,
        );
    }
}
