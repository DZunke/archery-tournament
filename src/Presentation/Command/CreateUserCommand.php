<?php

declare(strict_types=1);

namespace App\Presentation\Command;

use App\Domain\Entity\ArcheryGround;
use App\Domain\Entity\Tournament;
use App\Domain\Entity\User;
use App\Domain\Repository\ArcheryGroundRepository;
use App\Domain\Repository\TournamentRepository;
use App\Domain\Repository\UserRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;

use function array_filter;
use function array_map;
use function array_unique;
use function array_values;
use function implode;
use function is_array;
use function is_string;
use function sprintf;
use function strtolower;
use function trim;

#[AsCommand(
    name: 'app:user:create',
    description: 'Creates a user account for the application.',
)]
final class CreateUserCommand extends Command
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly PasswordHasherFactoryInterface $passwordHasherFactory,
        private readonly TournamentRepository $tournamentRepository,
        private readonly ArcheryGroundRepository $archeryGroundRepository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('username', null, InputOption::VALUE_REQUIRED, 'Username for the new user.')
            ->addOption('password', null, InputOption::VALUE_REQUIRED, 'Plain password for the new user.')
            ->addOption(
                'tournament',
                null,
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Tournament IDs to bind (repeat for multiple).',
            )
            ->addOption(
                'archery-ground',
                null,
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Archery ground IDs to bind (repeat for multiple).',
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $username = $this->resolveUsername($input, $io);
        if ($username === null) {
            return Command::FAILURE;
        }

        if ($this->userRepository->findByUsername($username) instanceof User) {
            $io->error(sprintf('A user with username "%s" already exists.', $username));

            return Command::FAILURE;
        }

        $plainPassword = $this->resolvePassword($input, $io);
        if ($plainPassword === null) {
            return Command::FAILURE;
        }

        $tournamentIds    = $this->normalizeIds($input->getOption('tournament'));
        $archeryGroundIds = $this->normalizeIds($input->getOption('archery-ground'));

        $missingTournaments = $this->findMissingTournaments($tournamentIds);
        if ($missingTournaments !== []) {
            $io->error(sprintf('Unknown tournament IDs: %s', implode(', ', $missingTournaments)));

            return Command::FAILURE;
        }

        $missingGrounds = $this->findMissingArcheryGrounds($archeryGroundIds);
        if ($missingGrounds !== []) {
            $io->error(sprintf('Unknown archery ground IDs: %s', implode(', ', $missingGrounds)));

            return Command::FAILURE;
        }

        $hasher       = $this->passwordHasherFactory->getPasswordHasher(User::class);
        $passwordHash = $hasher->hash($plainPassword);

        $user = new User(
            id: $this->userRepository->nextIdentity(),
            username: $username,
            passwordHash: $passwordHash,
            roles: ['ROLE_USER'],
            tournamentIds: $tournamentIds,
            archeryGroundIds: $archeryGroundIds,
        );

        $this->userRepository->save($user);

        $io->success(sprintf('User "%s" created.', $username));

        return Command::SUCCESS;
    }

    private function resolveUsername(InputInterface $input, SymfonyStyle $io): string|null
    {
        $usernameOption = $input->getOption('username');
        $username       = is_string($usernameOption) ? trim(strtolower($usernameOption)) : '';

        if ($username === '') {
            $username = trim(strtolower((string) $io->ask('Username')));
        }

        if ($username === '') {
            $io->error('Please provide a username.');

            return null;
        }

        return $username;
    }

    private function resolvePassword(InputInterface $input, SymfonyStyle $io): string|null
    {
        $passwordOption = $input->getOption('password');
        $plainPassword  = is_string($passwordOption) ? $passwordOption : '';

        if (trim($plainPassword) === '') {
            $plainPassword = (string) $io->askHidden('Password');
        }

        if (trim($plainPassword) === '') {
            $io->error('Password must not be empty.');

            return null;
        }

        return $plainPassword;
    }

    /** @return list<string> */
    private function normalizeIds(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        return array_values(array_unique(array_filter(array_map(
            static fn (mixed $id): string => trim((string) $id),
            $value,
        ), static fn (string $id): bool => $id !== '')));
    }

    /**
     * @param list<string> $tournamentIds
     *
     * @return list<string>
     */
    private function findMissingTournaments(array $tournamentIds): array
    {
        $missing = [];

        foreach ($tournamentIds as $tournamentId) {
            if ($this->tournamentRepository->find($tournamentId) instanceof Tournament) {
                continue;
            }

            $missing[] = $tournamentId;
        }

        return $missing;
    }

    /**
     * @param list<string> $archeryGroundIds
     *
     * @return list<string>
     */
    private function findMissingArcheryGrounds(array $archeryGroundIds): array
    {
        $missing = [];

        foreach ($archeryGroundIds as $archeryGroundId) {
            if ($this->archeryGroundRepository->find($archeryGroundId) instanceof ArcheryGround) {
                continue;
            }

            $missing[] = $archeryGroundId;
        }

        return $missing;
    }
}
