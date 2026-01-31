<?php

declare(strict_types=1);

namespace App\Presentation\Controller\ArcheryGround;

use App\Application\Bus\QueryBus;
use App\Application\Query\ArcheryGround\ListArcheryGrounds;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class IndexController extends AbstractController
{
    public function __construct(private readonly QueryBus $queryBus)
    {
    }

    #[Route('/archery-grounds', name: 'archery_ground_index', methods: ['GET'])]
    public function __invoke(): Response
    {
        $grounds = $this->queryBus->ask(new ListArcheryGrounds());

        return $this->render('archery_ground/index.html.twig', ['grounds' => $grounds]);
    }
}
