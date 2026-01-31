<?php

declare(strict_types=1);

namespace App\Application\Service\TournamentGenerator\Step;

use App\Application\Service\TournamentGenerator\DTO\TournamentResult;
use App\Application\Service\TournamentGenerator\Exception\TournamentGenerationFailed;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag]
interface TournamentGenerationStep
{
    /**
     * Returns the name of this step for logging and debugging purposes.
     */
    public function getName(): string;

    /**
     * Indicates if the step is supported by the given tournament result. Processing with this
     * step will only be attempted if this method returns true. This can, for example, check if
     * the tournament to be processed is following a required ruleset by this step.
     */
    public function supports(TournamentResult $tournamentResult): bool;

    /**
     * Process the tournament DTO and return a result indicating success or failure.
     * Each step validates its prerequisites and performs its specific transformation.
     *
     * @throws TournamentGenerationFailed if there are any issues during processing.
     */
    public function process(TournamentResult $tournamentResult): void;
}
