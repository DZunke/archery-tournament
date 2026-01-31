<?php

declare(strict_types=1);

namespace App\Presentation\Input\ArcheryGround;

use App\Application\Command\ArcheryGround\UpdateTargetImage;
use LogicException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;

final readonly class UpdateTargetImageInput
{
    public function __construct(public UploadedFile|null $image)
    {
    }

    public static function fromRequest(Request $request): self
    {
        return new self($request->files->get('image'));
    }

    /** @return list<string> */
    public function errors(): array
    {
        $errors = [];

        if (! $this->image instanceof UploadedFile) {
            $errors[] = 'Please upload an image for the target.';
        } elseif (! $this->image->isValid()) {
            $errors[] = 'The uploaded image could not be processed.';
        }

        return $errors;
    }

    public function toCommand(string $archeryGroundId, string $targetId): UpdateTargetImage
    {
        if (! $this->image instanceof UploadedFile) {
            throw new LogicException('Target image must be validated before building the command.');
        }

        return new UpdateTargetImage(
            archeryGroundId: $archeryGroundId,
            targetId: $targetId,
            image: $this->image,
        );
    }
}
