<?php

declare(strict_types=1);

namespace App\Presentation\Input\ArcheryGround;

use App\Application\Command\ArcheryGround\CreateArcheryGround;
use Symfony\Component\HttpFoundation\Request;

use function trim;

final readonly class CreateArcheryGroundInput
{
    public function __construct(public string $name)
    {
    }

    public static function fromRequest(Request $request): self
    {
        return new self((string) $request->request->get('name', ''));
    }

    /** @return list<string> */
    public function errors(): array
    {
        if (trim($this->name) === '') {
            return ['Please provide a name for the archery ground.'];
        }

        return [];
    }

    public function toCommand(): CreateArcheryGround
    {
        return new CreateArcheryGround(trim($this->name));
    }
}
