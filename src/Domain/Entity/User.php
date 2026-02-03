<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Webmozart\Assert\Assert;

use function array_merge;
use function array_unique;
use function array_values;

final class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    /**
     * @param list<string> $roles
     * @param list<string> $tournamentIds
     * @param list<string> $archeryGroundIds
     */
    public function __construct(
        private readonly string $id,
        private readonly string $username,
        private readonly string $passwordHash,
        private array $roles = ['ROLE_USER'],
        private readonly array $tournamentIds = [],
        private readonly array $archeryGroundIds = [],
    ) {
        Assert::uuid($this->id, 'The user id must be a valid UUID.');
        Assert::notEmpty($this->username, 'The username must not be empty.');
        Assert::notEmpty($this->passwordHash, 'The password hash must not be empty.');

        $this->roles = array_values(array_unique(array_merge($this->roles, ['ROLE_USER'])));
    }

    public function id(): string
    {
        return $this->id;
    }

    public function username(): string
    {
        return $this->username;
    }

    public function getUserIdentifier(): string
    {
        return $this->username;
    }

    /** @return list<string> */
    public function getRoles(): array
    {
        return $this->roles;
    }

    public function getPassword(): string
    {
        return $this->passwordHash;
    }

    /** @return list<string> */
    public function tournamentIds(): array
    {
        return $this->tournamentIds;
    }

    /** @return list<string> */
    public function archeryGroundIds(): array
    {
        return $this->archeryGroundIds;
    }
}
