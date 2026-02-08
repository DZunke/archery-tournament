<?php

declare(strict_types=1);

namespace App\Presentation\Input\ArcheryGround;

use App\Application\Command\ArcheryGround\UpdateTarget;
use App\Domain\ValueObject\TargetType;
use LogicException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;

use function trim;

final readonly class UpdateTargetInput
{
    public function __construct(
        public string $type,
        public string $name,
        public UploadedFile|null $image,
        public bool $forTrainingOnly,
        public string $notes,
    ) {
    }

    public static function fromRequest(Request $request): self
    {
        return new self(
            (string) $request->request->get('type', ''),
            (string) $request->request->get('name', ''),
            $request->files->get('image'),
            $request->request->getBoolean('for_training_only'),
            (string) $request->request->get('notes', ''),
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

        // Image is optional for updates - only validate if provided
        if ($this->image instanceof UploadedFile && ! $this->image->isValid()) {
            $errors[] = 'The uploaded image could not be processed.';
        }

        return $errors;
    }

    public function toCommand(string $archeryGroundId, string $targetId): UpdateTarget
    {
        $type = TargetType::tryFrom($this->type);
        if (! $type instanceof TargetType) {
            throw new LogicException('Target type must be validated before building the command.');
        }

        return new UpdateTarget(
            archeryGroundId: $archeryGroundId,
            targetId: $targetId,
            name: trim($this->name),
            type: $type,
            forTrainingOnly: $this->forTrainingOnly,
            notes: $this->notes,
            image: $this->image,
        );
    }
}
