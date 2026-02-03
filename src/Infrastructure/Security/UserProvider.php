<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

use App\Domain\Entity\User;
use App\Domain\Repository\UserRepository;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

use function is_subclass_of;
use function sprintf;
use function strtolower;
use function trim;

/** @implements UserProviderInterface<User> */
final readonly class UserProvider implements UserProviderInterface
{
    public function __construct(private UserRepository $userRepository)
    {
    }

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        $normalizedIdentifier = strtolower(trim($identifier));
        $user                 = $this->userRepository->findByUsername($normalizedIdentifier);
        if (! $user instanceof User) {
            $exception = new UserNotFoundException(sprintf('User "%s" not found.', $normalizedIdentifier));
            $exception->setUserIdentifier($normalizedIdentifier);

            throw $exception;
        }

        return $user;
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        if (! $user instanceof User) {
            throw new UnsupportedUserException(sprintf('Unsupported user class "%s".', $user::class));
        }

        $reloaded = $this->userRepository->find($user->id());
        if (! $reloaded instanceof User) {
            $exception = new UserNotFoundException(sprintf('User "%s" not found.', $user->getUserIdentifier()));
            $exception->setUserIdentifier($user->getUserIdentifier());

            throw $exception;
        }

        return $reloaded;
    }

    public function supportsClass(string $class): bool
    {
        return $class === User::class || is_subclass_of($class, User::class);
    }
}
