<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\User;

interface UserRepository
{
    public function nextIdentity(): string;

    public function save(User $user): void;

    public function find(string $id): User|null;

    public function findByUsername(string $username): User|null;
}
