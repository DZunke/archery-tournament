<?php

declare(strict_types=1);

namespace App\Presentation\Controller\Tournament;

use App\Application\Bus\QueryBus;
use App\Application\Query\Tournament\GetTournament;
use App\Application\Service\TournamentValidation\TournamentValidationContext;
use App\Application\Service\TournamentValidation\TournamentValidator;
use App\Domain\Entity\Tournament;
use App\Domain\ValueObject\TargetType;
use App\Presentation\View\TournamentAssignmentViewBuilder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

use function array_key_first;
use function array_keys;

final class ValidateController extends AbstractController
{
    public function __construct(
        private readonly QueryBus $queryBus,
        private readonly TournamentValidator $tournamentValidator,
        private readonly TournamentAssignmentViewBuilder $assignmentViewBuilder,
    ) {
    }

    #[Route('/tournaments/{id}/validate', name: 'tournament_validate', methods: ['POST'])]
    public function __invoke(Request $request, string $id): Response
    {
        $token = (string) $request->request->get('_token');
        if (! $this->isCsrfTokenValid('tournament_validate_' . $id, $token)) {
            $this->addFlash('error', 'Invalid CSRF token.');

            return $this->redirectToRoute('tournament_show', ['id' => $id]);
        }

        $tournament = $this->queryBus->ask(new GetTournament($id));
        if (! $tournament instanceof Tournament) {
            throw $this->createNotFoundException('Tournament not found.');
        }

        $validationResult = $this->tournamentValidator->validate(
            TournamentValidationContext::fromTournament($tournament),
        );
        if ($validationResult->isValid()) {
            $this->addFlash('success', 'Tournament validation passed.');
        } else {
            $this->addFlash('error', 'Tournament validation found issues.');
        }

        $ruleset       = $tournament->ruleset();
        $targetTypes   = $ruleset->allowedTargetTypes();
        $firstType     = $targetTypes[array_key_first($targetTypes)] ?? TargetType::ANIMAL_GROUP_1;
        $stakeKeys     = array_keys($ruleset->stakeDistanceRanges($firstType));
        $sortedTargets = $this->assignmentViewBuilder->sortTargets($tournament);

        return $this->render('tournament/show.html.twig', [
            'tournament' => $tournament,
            'archeryGround' => $tournament->archeryGround(),
            'stakeKeys' => $stakeKeys,
            'validationResult' => $validationResult,
            'sortedTargets' => $sortedTargets,
        ]);
    }
}
