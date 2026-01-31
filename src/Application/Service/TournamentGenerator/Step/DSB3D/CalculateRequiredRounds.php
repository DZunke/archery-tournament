<?php

declare(strict_types=1);

namespace App\Application\Service\TournamentGenerator\Step\DSB3D;

use App\Application\Service\TournamentGenerator\DTO\TournamentResult;
use App\Application\Service\TournamentGenerator\Exception\NotEnoughLanesAtShootingRange;
use App\Application\Service\TournamentGenerator\Exception\TournamentGenerationFailed;
use App\Application\Service\TournamentGenerator\Step\TournamentGenerationStep;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

use function ceil;
use function count;

#[AsTaggedItem(priority: 490)]
final readonly class CalculateRequiredRounds implements TournamentGenerationStep
{
    public function __construct(private readonly LoggerInterface $logger)
    {
    }

    public function getName(): string
    {
        return 'Calculate Required Rounds at archery ground';
    }

    public function supports(TournamentResult $tournamentResult): bool
    {
        return true;
    }

    public function process(TournamentResult $tournamentResult): void
    {
        $numberOfTargets     = $tournamentResult->numberOfTargets;
        $qualifiedLanesCount = count($tournamentResult->availableLanes);

        if ($qualifiedLanesCount === 0) {
            throw NotEnoughLanesAtShootingRange::notQualifiedLanesAtShootingRange();
        }

        $totalRequiredTargetTypes = $tournamentResult->ruleset->requiredTargetTypes();
        if (count($totalRequiredTargetTypes) === 0) {
            throw new TournamentGenerationFailed('No target types defined in the ruleset for the tournament.');
        }

        $roundsNeeded                     = (int) ceil($numberOfTargets / $qualifiedLanesCount);
        $tournamentResult->requiredRounds = $roundsNeeded;

        $this->logger->debug(
            'Calculated required rounds for the tournament: ' . $roundsNeeded . ' rounds needed.',
            [
                'number_of_targets' => $numberOfTargets,
                'qualified_lanes_count' => $qualifiedLanesCount,
                'rounds_needed' => $roundsNeeded,
            ],
        );
    }
}
