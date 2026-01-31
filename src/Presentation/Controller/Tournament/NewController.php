<?php

declare(strict_types=1);

namespace App\Presentation\Controller\Tournament;

use App\Application\Bus\QueryBus;
use App\Application\Query\ArcheryGround\ListArcheryGrounds;
use App\Domain\ValueObject\Ruleset;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class NewController extends AbstractController
{
    public function __construct(private readonly QueryBus $queryBus)
    {
    }

    #[Route('/tournaments/new', name: 'tournament_new', methods: ['GET'])]
    public function __invoke(Request $request): Response
    {
        $grounds          = $this->queryBus->ask(new ListArcheryGrounds());
        $selectedGroundId = $request->query->get('archery_ground_id', '');
        $selectedMode     = $request->query->get('mode', 'auto');

        return $this->render('tournament/new.html.twig', [
            'grounds' => $grounds,
            'rulesets' => Ruleset::cases(),
            'selectedGroundId' => $selectedGroundId,
            'selectedMode' => $selectedMode,
        ]);
    }
}
