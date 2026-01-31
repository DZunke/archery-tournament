<?php

declare(strict_types=1);

namespace App\Presentation\Input\ArcheryGround;

use App\Application\Command\ArcheryGround\RenameArcheryGround;
use Symfony\Component\HttpFoundation\Request;

use function trim;

final readonly class RenameArcheryGroundInput
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
            return ['Name cannot be empty.'];
        }

        return [];
    }

    public function toCommand(string $id): RenameArcheryGround
    {
        return new RenameArcheryGround($id, trim($this->name));
    }
}
