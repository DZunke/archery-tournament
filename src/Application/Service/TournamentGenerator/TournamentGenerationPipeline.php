<?php

declare(strict_types=1);

namespace App\Application\Service\TournamentGenerator;

use App\Application\Service\TournamentGenerator\DTO\TournamentGenerationRequest;
use App\Application\Service\TournamentGenerator\DTO\TournamentResult;
use App\Application\Service\TournamentGenerator\Step\TournamentGenerationStep;
use App\Domain\Entity\Tournament;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

final class TournamentGenerationPipeline
{
    /** @param iterable<TournamentGenerationStep> $steps */
    public function __construct(
        #[AutowireIterator(tag: TournamentGenerationStep::class)] private readonly iterable $steps,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function generate(TournamentGenerationRequest $request): Tournament
    {
        $this->logger->debug('Starting tournament generation pipeline', [
            'archery_ground' => $request->archeryGround->name(),
            'ruleset' => $request->ruleset->name(),
            'amount_of_targets' => $request->amountOfTargets,
            'randomize_stakes_between_rounds' => $request->randomizeStakesBetweenRounds,
        ]);

        $tournamentResult = new TournamentResult(
            $request->archeryGround,
            $request->ruleset,
            $request->amountOfTargets,
            $request->randomizeStakesBetweenRounds,
        );
        foreach ($this->steps as $step) {
            if (! $step->supports($tournamentResult)) {
                continue; // Silently filter out unqualified steps
            }

            $stepName = $step->getName();
            $this->logger->debug('Executing step "' . $stepName . '"', ['step' => $stepName]);

            $step->process($tournamentResult);

            $this->logger->debug('Step completed successfully', ['step' => $stepName]);
        }

        $this->logger->debug('Finished tournament generation pipeline', [
            'archery_ground' => $request->archeryGround->name(),
            'ruleset' => $request->ruleset->name(),
            'amount_of_targets' => $request->amountOfTargets,
            'randomize_stakes_between_rounds' => $request->randomizeStakesBetweenRounds,
        ]);

        $tournament = Tournament::create(
            'Generated Tournament',
            $request->ruleset,
            $request->archeryGround,
            $request->amountOfTargets,
        );
        $tournament->replaceTargets($tournamentResult->targets);

        return $tournament;
    }
}
