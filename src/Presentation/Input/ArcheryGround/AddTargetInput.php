<?php

declare(strict_types=1);

namespace App\Presentation\Input\ArcheryGround;

use App\Application\Command\ArcheryGround\AddTarget;
use App\Domain\ValueObject\TargetType;
use LogicException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;

use function trim;

final readonly class AddTargetInput
{
    public function __construct(
        public string $type,
        public string $name,
        public UploadedFile|null $image,
    ) {
    }

    public static function fromRequest(Request $request): self
    {
        return new self(
            (string) $request->request->get('type', ''),
            (string) $request->request->get('name', ''),
            $request->files->get('image'),
        );
    }

    /** @return list<string> */
    public function errors(): array
    {
        $errors = [];

        if (trim($this->name) === '') {
            $errors[] = 'Target name is required.';
        }

        if (TargetType::tryFrom($this->type) === null) {
            $errors[] = 'Target type is invalid.';
        }

        if (! $this->image instanceof UploadedFile) {
            $errors[] = 'Please upload an image for the target.';
        } elseif (! $this->image->isValid()) {
            $errors[] = 'The uploaded image could not be processed.';
        }

        return $errors;
    }

    public function toCommand(string $archeryGroundId): AddTarget
    {
        $type = TargetType::tryFrom($this->type);
        if (! $type instanceof TargetType) {
            throw new LogicException('Target type must be validated before building the command.');
        }

        if (! $this->image instanceof UploadedFile) {
            throw new LogicException('Target image must be validated before building the command.');
        }

        return new AddTarget(
            $archeryGroundId,
            $type,
            trim($this->name),
            $this->image,
        );
    }
}
