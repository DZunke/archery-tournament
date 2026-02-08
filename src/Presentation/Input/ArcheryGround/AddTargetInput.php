<?php

declare(strict_types=1);

namespace App\Presentation\Input\ArcheryGround;

use App\Application\Command\ArcheryGround\AddTarget;
use App\Domain\ValueObject\TargetType;
use App\Domain\ValueObject\TargetZoneSizeConverter;
use LogicException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;

use function is_numeric;
use function trim;

final readonly class AddTargetInput
{
    public function __construct(
        public string $type,
        public string $name,
        public UploadedFile|null $image,
        public bool $forTrainingOnly,
        public string $notes,
        public string $targetZoneSize,
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
            (string) $request->request->get('target_zone_size', ''),
        );
    }

    /** @return list<string> */
    public function errors(): array
    {
        $errors      = [];
        $hasZoneSize = trim($this->targetZoneSize) !== '';
        $zoneSizeInt = null;

        if (trim($this->name) === '') {
            $errors[] = 'Target name is required.';
        }

        if ($hasZoneSize) {
            if (! is_numeric($this->targetZoneSize)) {
                $errors[] = 'Target zone size must be a number.';
            } else {
                $zoneSizeInt = (int) $this->targetZoneSize;
                if ($zoneSizeInt <= 0) {
                    $errors[] = 'Target zone size must be greater than 0.';
                }
            }
        } else {
            // When no zone size, type must be manually selected
            if (TargetType::tryFrom($this->type) === null) {
                $errors[] = 'Target type is required when no zone size is provided.';
            }
        }

        // When zone size is provided, validate the type matches
        if ($hasZoneSize && $zoneSizeInt !== null && $zoneSizeInt > 0) {
            $derivedType  = TargetZoneSizeConverter::toTargetType($zoneSizeInt);
            $selectedType = TargetType::tryFrom($this->type);
            if ($selectedType !== null && $selectedType !== $derivedType) {
                $errors[] = 'Target type does not match the zone size. Expected ' . $derivedType->value . '.';
            }
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
        $hasZoneSize = trim($this->targetZoneSize) !== '';
        $zoneSizeInt = $hasZoneSize ? (int) $this->targetZoneSize : null;

        // When zone size is provided, derive the type from it
        $type = $hasZoneSize && $zoneSizeInt !== null
            ? TargetZoneSizeConverter::toTargetType($zoneSizeInt)
            : TargetType::tryFrom($this->type);

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
            $this->forTrainingOnly,
            $this->notes,
            $zoneSizeInt,
        );
    }
}
