<?php

declare(strict_types=1);

namespace App\Presentation\Controller\Tournament;

use App\Application\Bus\CommandBus;
use App\Application\Bus\QueryBus;
use App\Application\Query\Tournament\GetTournament;
use App\Application\Service\TournamentValidation\TournamentValidationIssue;
use App\Domain\Entity\Tournament;
use App\Domain\ValueObject\TargetType;
use App\Presentation\Input\Tournament\UpdateTournamentTargetsInput;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

use function array_filter;
use function array_key_first;
use function array_keys;
use function array_values;
use function ctype_digit;
use function is_array;
use function is_int;

final class UpdateTargetsController extends AbstractController
{
    public function __construct(
        private readonly CommandBus $commandBus,
        private readonly QueryBus $queryBus,
    ) {
    }

    #[Route('/tournaments/{id}/targets', name: 'tournament_update_targets', methods: ['POST'])]
    public function __invoke(Request $request, string $id): Response
    {
        $input  = UpdateTournamentTargetsInput::fromRequest($request);
        $issues = $input->issues();

        if ($issues !== []) {
            return $this->renderWithDraft($id, $input->rows(), $issues);
        }

        $result = $this->commandBus->dispatch($input->toCommand($id));

        if (! $result->success) {
            $issues = $result->data['issues'] ?? [];
            if (! is_array($issues)) {
                $issues = [];
            }

            $issues = array_values(array_filter(
                $issues,
                static fn (mixed $issue): bool => $issue instanceof TournamentValidationIssue,
            ));

            if ($issues === []) {
                $issues[] = new TournamentValidationIssue(
                    rule: 'Validation',
                    message: (string) $result->message,
                );
            }

            return $this->renderWithDraft($id, $input->rows(), $issues);
        }

        $this->addFlash('success', (string) $result->message);

        return $this->redirectToRoute('tournament_show', ['id' => $id]);
    }

    /**
     * @param list<array{rowIndex: int, round: int, shootingLaneId: string, targetId: string, stakes: array<string,int>}> $rows
     * @param list<TournamentValidationIssue>                                                                             $issues
     */
    private function renderWithDraft(string $id, array $rows, array $issues): Response
    {
        $tournament = $this->queryBus->ask(new GetTournament($id));
        if (! $tournament instanceof Tournament) {
            throw $this->createNotFoundException('Tournament not found.');
        }

        $ruleset     = $tournament->ruleset();
        $targetTypes = $ruleset->allowedTargetTypes();
        $firstType   = $targetTypes[array_key_first($targetTypes)] ?? TargetType::ANIMAL_GROUP_1;
        $stakeKeys   = array_keys($ruleset->stakeDistanceRanges($firstType));

        [$issuesByRow, $generalIssues] = $this->groupIssuesByRow($issues);

        return $this->render('tournament/show.html.twig', [
            'tournament' => $tournament,
            'archeryGround' => $tournament->archeryGround(),
            'stakeKeys' => $stakeKeys,
            'draftAssignments' => $rows,
            'saveIssues' => $generalIssues,
            'saveIssuesByRow' => $issuesByRow,
        ]);
    }

    /**
     * @param list<TournamentValidationIssue> $issues
     *
     * @return array{0: array<int, list<string>>, 1: list<string>}
     */
    private function groupIssuesByRow(array $issues): array
    {
        $issuesByRow   = [];
        $generalIssues = [];

        foreach ($issues as $issue) {
            $row = $issue->context['row'] ?? null;
            if (is_int($row) || ctype_digit((string) $row)) {
                $rowNumber                 = (int) $row;
                $issuesByRow[$rowNumber][] = $issue->message;
                continue;
            }

            $generalIssues[] = $issue->message;
        }

        return [$issuesByRow, $generalIssues];
    }
}
