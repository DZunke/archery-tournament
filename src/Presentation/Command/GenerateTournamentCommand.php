<?php

declare(strict_types=1);

namespace App\Presentation\Command;

use App\Application\Query\GetArcheryGroundQuery;
use App\Application\Service\TournamentGenerator\DTO\TournamentGenerationRequest;
use App\Application\Service\TournamentGenerator\TournamentGenerationPipeline;
use App\Domain\ValueObject\Ruleset;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Style\SymfonyStyle;

use function array_keys;
use function array_merge;

#[AsCommand(
    name: 'app:generate-tournament',
    description: 'Generates a new tournament.',
)]
final class GenerateTournamentCommand
{
    public function __construct(
        private readonly GetArcheryGroundQuery $getArcheryGroundQuery,
        private readonly TournamentGenerationPipeline $tournamentGenerationPipeline,
    ) {
    }

    public function __invoke(
        SymfonyStyle $io,
        #[Option(
            name: 'randomize-stakes-between-rounds',
            shortcut: 'r',
            description: 'Randomize stake distances between rounds while keeping targets fixed.',
        )]
        bool $randomizeStakesBetweenRounds = false,
    ): int {
        $io->title('Generate Tournament Command');

        // todo: ask and fetch the correct archery ground to use
        $archeryGround = $this->getArcheryGroundQuery->query();
        // todo: implement to ask for the amount of targets for this tournament
        $amountOfTargets = 24;
        // todo: implement to ask for the ruleset for this tournament
        $ruleset = Ruleset::from('DSB_3D');

        $table = new Table($io);
        $table->setHeaders(['Archery Ground', 'Number of Targets', 'Ruleset', 'Randomize Stakes']);
        $table->setRows([
            [$archeryGround->name(), $amountOfTargets, $ruleset->value, $randomizeStakesBetweenRounds ? 'yes' : 'no'],
        ]);
        $table->render();

        $tournament = $this->tournamentGenerationPipeline->generate(
            new TournamentGenerationRequest(
                archeryGround: $archeryGround,
                ruleset: $ruleset,
                amountOfTargets: $amountOfTargets,
                randomizeStakesBetweenRounds: $randomizeStakesBetweenRounds,
            ),
        );

        $assignments = $tournament->targets();

        $table        = new Table($io);
        $stakeHeaders = [];
        foreach ($assignments as $assignment) {
            $stakeHeaders = array_keys($assignment->stakes()->all());
            break;
        }

        $table->setHeaders(array_merge(['Round', 'Shooting Lane', 'Target Type', 'Target'], $stakeHeaders));
        $rows = [];
        foreach ($assignments as $assignment) {
            $row = [
                $assignment->round(),
                $assignment->shootingLane()->name(),
                $assignment->target()->type()->value,
                $assignment->target()->name(),
            ];
            foreach ($stakeHeaders as $stake) {
                $row[] = $assignment->stakes()->has($stake) ? $assignment->stakes()->get($stake) : '-';
            }

            $rows[] = $row;
        }

        $table->setRows($rows);
        $table->render();

        $io->note('... Tournament validation passed with no errors.');

        $io->success('Tournament generated successfully.');

        return Command::SUCCESS;
    }
}
