<?php

declare(strict_types=1);

namespace App\Presentation\Command;

use App\Application\Query\GetArcheryGroundQuery;
use App\Application\Service\TournamentRandomCalculator;
use App\Domain\Entity\Tournament;
use App\Domain\ValueObject\Ruleset;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:generate-tournament',
    description: 'Generates a new tournament.',
)]
final class GenerateTournamentCommand
{
    public function __construct(
        private readonly GetArcheryGroundQuery $getArcheryGroundQuery,
    ) {
    }

    public function __invoke(SymfonyStyle $io): int
    {
        $io->title('Generate Tournament Command');

        // todo: ask and fetch the correct archery ground to use
        $archeryGround = $this->getArcheryGroundQuery->query();
        // todo: implement to ask for the amount of targets for this tournament
        $amountOfTargets = 24;
        // todo: implement to ask for the ruleset for this tournament
        $ruleset = Ruleset::from('DSB_3D');

        $table = new Table($io);
        $table->setHeaders(['Archery Ground', 'Number of Targets', 'Ruleset']);
        $table->setRows([
            [$archeryGround->name(), $amountOfTargets, $ruleset->value],
        ]);
        $table->render();

        $tournament = Tournament::create(
            name: 'New Tournament',
            ruleset: $ruleset,
            archeryGround: $archeryGround,
            numberOfTargets: $amountOfTargets,
        );

        $tournamentTarget = new TournamentRandomCalculator();
        $assignments      = $tournamentTarget->calculate($tournament);

        $table = new Table($io);
        $table->setHeaders(['Round', 'Shooting Lane', 'Target Type', 'Target']);
        $rows = [];
        foreach ($assignments as $assignment) {
            $rows[] = [
                $assignment['round'],
                $assignment['shootingLane']->name(),
                $assignment['target']->type()->value,
                $assignment['target']->name(),
            ];
        }

        $table->setRows($rows);
        $table->render();

        $io->success('Tournament generated successfully.');

        return Command::SUCCESS;
    }
}
