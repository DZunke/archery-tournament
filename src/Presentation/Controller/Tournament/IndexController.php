<?php

declare(strict_types=1);

namespace App\Presentation\Controller\Tournament;

use App\Application\Bus\QueryBus;
use App\Application\Query\Tournament\ListTournaments;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class IndexController extends AbstractController
{
    public function __construct(private readonly QueryBus $queryBus)
    {
    }

    #[Route('/tournaments', name: 'tournament_index', methods: ['GET'])]
    public function __invoke(): Response
    {
        $tournaments = $this->queryBus->ask(new ListTournaments());

        return $this->render('tournament/index.html.twig', ['tournaments' => $tournaments]);
    }
}
