<?php

declare(strict_types=1);

namespace App\Application\Command;

final readonly class CommandResult
{
    /** @param array<string, mixed> $data */
    private function __construct(
        public bool $success,
        public string|null $message = null,
        public array $data = [],
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function success(string|null $message = null, array $data = []): self
    {
        return new self(true, $message, $data);
    }

    public static function failure(string $message): self
    {
        return new self(false, $message);
    }
}
