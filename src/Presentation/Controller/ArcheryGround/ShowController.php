<?php

declare(strict_types=1);

namespace App\Presentation\Controller\ArcheryGround;

use App\Application\Bus\QueryBus;
use App\Application\Query\ArcheryGround\GetArcheryGround;
use App\Domain\Entity\ArcheryGround;
use App\Domain\ValueObject\TargetType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ShowController extends AbstractController
{
    public function __construct(private readonly QueryBus $queryBus)
    {
    }

    #[Route('/archery-grounds/{id}', name: 'archery_ground_show', methods: ['GET'])]
    public function __invoke(string $id): Response
    {
        $archeryGround = $this->queryBus->ask(new GetArcheryGround($id));
        if (! $archeryGround instanceof ArcheryGround) {
            throw $this->createNotFoundException('Archery ground not found.');
        }

        return $this->render('archery_ground/show.html.twig', [
            'archeryGround' => $archeryGround,
            'targetTypes' => TargetType::cases(),
        ]);
    }
}
