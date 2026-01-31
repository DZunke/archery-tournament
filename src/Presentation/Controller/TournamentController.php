<?php

declare(strict_types=1);

namespace App\Presentation\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class TournamentController extends AbstractController
{
    #[Route('/tournaments', name: 'tournament_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('tournament/index.html.twig');
    }
}
