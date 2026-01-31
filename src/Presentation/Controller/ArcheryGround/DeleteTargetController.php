<?php

declare(strict_types=1);

namespace App\Presentation\Controller\ArcheryGround;

use App\Application\Bus\CommandBus;
use App\Application\Command\ArcheryGround\RemoveTarget;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DeleteTargetController extends AbstractController
{
    public function __construct(private readonly CommandBus $commandBus)
    {
    }

    #[Route('/archery-grounds/{id}/targets/{targetId}/delete', name: 'archery_ground_delete_target', methods: ['POST'])]
    public function __invoke(Request $request, string $id, string $targetId): Response
    {
        $token = (string) $request->request->get('_token');
        if (! $this->isCsrfTokenValid('archery_ground_delete_target_' . $id . '_' . $targetId, $token)) {
            $this->addFlash('error', 'Invalid CSRF token.');

            return $this->redirectToRoute('archery_ground_show', ['id' => $id]);
        }

        $result = $this->commandBus->dispatch(new RemoveTarget(
            archeryGroundId: $id,
            targetId: $targetId,
        ));

        if ($result->success) {
            $this->addFlash('success', (string) $result->message);
        } else {
            $this->addFlash('error', (string) $result->message);
        }

        return $this->redirectToRoute('archery_ground_show', ['id' => $id]);
    }
}
