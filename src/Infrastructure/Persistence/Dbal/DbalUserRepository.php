<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Dbal;

use App\Domain\Entity\User;
use App\Domain\Repository\UserRepository;
use Doctrine\DBAL\Connection;
use JsonException;
use Symfony\Component\Uid\Uuid;

use function array_filter;
use function array_map;
use function array_unique;
use function array_values;
use function in_array;
use function is_array;
use function is_string;
use function json_decode;
use function json_encode;
use function trim;

use const JSON_THROW_ON_ERROR;

final readonly class DbalUserRepository implements UserRepository
{
    public function __construct(private Connection $connection)
    {
    }

    public function nextIdentity(): string
    {
        return Uuid::v4()->toRfc4122();
    }

    public function save(User $user): void
    {
        $exists = $this->connection->fetchOne(
            'SELECT 1 FROM users WHERE id = ?',
            [$user->id()],
        );

        $payload = [
            'id' => $user->id(),
            'username' => $user->username(),
            'password' => $user->getPassword(),
            'roles' => $this->encodeRoles($user->getRoles()),
        ];

        if ($exists !== false) {
            $this->connection->executeStatement(
                'UPDATE users SET username = ?, password = ?, roles = ? WHERE id = ?',
                [$payload['username'], $payload['password'], $payload['roles'], $payload['id']],
            );
        } else {
            $this->connection->insert('users', $payload);
        }

        $this->replaceUserTournaments($user->id(), $user->tournamentIds());
        $this->replaceUserArcheryGrounds($user->id(), $user->archeryGroundIds());
    }

    public function find(string $id): User|null
    {
        $row = $this->connection->fetchAssociative(
            'SELECT id, username, password, roles FROM users WHERE id = ?',
            [$id],
        );

        if ($row === false) {
            return null;
        }

        return $this->hydrateUser($row);
    }

    public function findByUsername(string $username): User|null
    {
        $row = $this->connection->fetchAssociative(
            'SELECT id, username, password, roles FROM users WHERE username = ?',
            [$username],
        );

        if ($row === false) {
            return null;
        }

        return $this->hydrateUser($row);
    }

    /** @param array{id: string, username: string, password: string, roles: string} $row */
    private function hydrateUser(array $row): User
    {
        $userId = (string) $row['id'];

        return new User(
            id: $userId,
            username: (string) $row['username'],
            passwordHash: (string) $row['password'],
            roles: $this->decodeRoles((string) $row['roles']),
            tournamentIds: $this->loadTournamentIds($userId),
            archeryGroundIds: $this->loadArcheryGroundIds($userId),
        );
    }

    /** @param list<string> $tournamentIds */
    private function replaceUserTournaments(string $userId, array $tournamentIds): void
    {
        $this->connection->executeStatement('DELETE FROM user_tournaments WHERE user_id = ?', [$userId]);

        foreach ($this->normalizeIds($tournamentIds) as $tournamentId) {
            $this->connection->insert('user_tournaments', [
                'user_id' => $userId,
                'tournament_id' => $tournamentId,
            ]);
        }
    }

    /** @param list<string> $archeryGroundIds */
    private function replaceUserArcheryGrounds(string $userId, array $archeryGroundIds): void
    {
        $this->connection->executeStatement('DELETE FROM user_archery_grounds WHERE user_id = ?', [$userId]);

        foreach ($this->normalizeIds($archeryGroundIds) as $archeryGroundId) {
            $this->connection->insert('user_archery_grounds', [
                'user_id' => $userId,
                'archery_ground_id' => $archeryGroundId,
            ]);
        }
    }

    /** @return list<string> */
    private function loadTournamentIds(string $userId): array
    {
        $rows = $this->connection->fetchAllAssociative(
            'SELECT tournament_id FROM user_tournaments WHERE user_id = ? ORDER BY tournament_id',
            [$userId],
        );

        return array_values(array_map(
            static fn (array $row): string => (string) $row['tournament_id'],
            $rows,
        ));
    }

    /** @return list<string> */
    private function loadArcheryGroundIds(string $userId): array
    {
        $rows = $this->connection->fetchAllAssociative(
            'SELECT archery_ground_id FROM user_archery_grounds WHERE user_id = ? ORDER BY archery_ground_id',
            [$userId],
        );

        return array_values(array_map(
            static fn (array $row): string => (string) $row['archery_ground_id'],
            $rows,
        ));
    }

    /** @param list<string> $roles */
    private function encodeRoles(array $roles): string
    {
        $roles = array_values(array_unique($roles));

        try {
            return json_encode($roles, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return '[]';
        }
    }

    /** @return list<string> */
    private function decodeRoles(string $payload): array
    {
        try {
            $decoded = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return ['ROLE_USER'];
        }

        if (! is_array($decoded)) {
            return ['ROLE_USER'];
        }

        $roles = array_values(array_unique(array_filter(array_map(
            static fn (mixed $role): string => is_string($role) ? $role : '',
            $decoded,
        ), static fn (string $role): bool => trim($role) !== '')));

        if (! in_array('ROLE_USER', $roles, true)) {
            $roles[] = 'ROLE_USER';
        }

        return $roles;
    }

    /** @param list<string> $ids */
    private function normalizeIds(array $ids): array
    {
        return array_values(array_unique(array_filter(array_map(
            static fn (string $value): string => trim($value),
            $ids,
        ), static fn (string $value): bool => $value !== '')));
    }
}
