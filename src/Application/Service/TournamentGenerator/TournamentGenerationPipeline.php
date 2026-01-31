<?php

declare(strict_types=1);

namespace App\Application\Service\TournamentGenerator;

use App\Application\Service\TournamentGenerator\DTO\TournamentResult;
use App\Application\Service\TournamentGenerator\Step\TournamentGenerationStep;
use App\Domain\Entity\ArcheryGround;
use App\Domain\Entity\Tournament;
use App\Domain\ValueObject\Ruleset;
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

    public function generate(ArcheryGround $archeryGround, Ruleset $ruleset, int $amountOfTargets): Tournament
    {
        $this->logger->debug('Starting tournament generation pipeline', [
            'archery_ground' => $archeryGround->name(),
            'ruleset' => $ruleset->name(),
            'amount_of_targets' => $amountOfTargets,
        ]);

        $tournamentResult = new TournamentResult($archeryGround, $ruleset, $amountOfTargets);
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
            'archery_ground' => $archeryGround->name(),
            'ruleset' => $ruleset->name(),
            'amount_of_targets' => $amountOfTargets,
        ]);

        return Tournament::create('Generated Tournament', $ruleset, $archeryGround, $amountOfTargets);
    }
}
