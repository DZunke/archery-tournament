<?php

declare(strict_types=1);

namespace App\Presentation\Controller\Tournament;

use App\Application\Bus\QueryBus;
use App\Application\Query\Tournament\GetTournament;
use App\Domain\Entity\Tournament;
use App\Domain\ValueObject\TargetType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

use function array_key_first;
use function array_keys;

final class ShowController extends AbstractController
{
    public function __construct(private readonly QueryBus $queryBus)
    {
    }

    #[Route('/tournaments/{id}', name: 'tournament_show', methods: ['GET'])]
    public function __invoke(string $id): Response
    {
        $tournament = $this->queryBus->ask(new GetTournament($id));
        if (! $tournament instanceof Tournament) {
            throw $this->createNotFoundException('Tournament not found.');
        }

        $ruleset     = $tournament->ruleset();
        $targetTypes = $ruleset->allowedTargetTypes();
        $firstType   = $targetTypes[array_key_first($targetTypes)] ?? TargetType::ANIMAL_GROUP_1;
        $stakeKeys   = array_keys($ruleset->stakeDistanceRanges($firstType));

        return $this->render('tournament/show.html.twig', [
            'tournament' => $tournament,
            'archeryGround' => $tournament->archeryGround(),
            'stakeKeys' => $stakeKeys,
        ]);
    }
}
