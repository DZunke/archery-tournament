<?php

declare(strict_types=1);

namespace App\Presentation\Controller\ArcheryGround;

use App\Application\Bus\CommandBus;
use App\Application\Command\ArcheryGround\RemoveShootingLane;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DeleteLaneController extends AbstractController
{
    public function __construct(private readonly CommandBus $commandBus)
    {
    }

    #[Route('/archery-grounds/{id}/lanes/{laneId}/delete', name: 'archery_ground_delete_lane', methods: ['POST'])]
    public function __invoke(Request $request, string $id, string $laneId): Response
    {
        $token = (string) $request->request->get('_token');
        if (! $this->isCsrfTokenValid('archery_ground_delete_lane_' . $id . '_' . $laneId, $token)) {
            $this->addFlash('error', 'Invalid CSRF token.');

            return $this->redirectToRoute('archery_ground_show', ['id' => $id]);
        }

        $result = $this->commandBus->dispatch(new RemoveShootingLane(
            archeryGroundId: $id,
            laneId: $laneId,
        ));

        if ($result->success) {
            $this->addFlash('success', (string) $result->message);
        } else {
            $this->addFlash('error', (string) $result->message);
        }

        return $this->redirectToRoute('archery_ground_show', ['id' => $id]);
    }
}
