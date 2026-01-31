<?php

declare(strict_types=1);

namespace App\Presentation\Controller\ArcheryGround;

use App\Application\Command\ArcheryGround\AddShootingLane;
use App\Application\Command\ArcheryGround\AddShootingLaneHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AddLaneController extends AbstractController
{
    public function __construct(private readonly AddShootingLaneHandler $addShootingLaneHandler)
    {
    }

    #[Route('/archery-grounds/{id}/lanes', name: 'archery_ground_add_lane', methods: ['POST'])]
    public function __invoke(Request $request, string $id): Response
    {
        $result = ($this->addShootingLaneHandler)(new AddShootingLane(
            archeryGroundId: $id,
            name: (string) $request->request->get('name', ''),
            maxDistance: (string) $request->request->get('max_distance', ''),
        ));

        if ($result->success) {
            $this->addFlash('success', (string) $result->message);
        } else {
            $this->addFlash('error', (string) $result->message);
        }

        return $this->redirectToRoute('archery_ground_show', ['id' => $id]);
    }
}
