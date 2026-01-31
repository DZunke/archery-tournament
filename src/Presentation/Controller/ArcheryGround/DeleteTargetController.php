<?php

declare(strict_types=1);

namespace App\Presentation\Controller\ArcheryGround;

use App\Application\Command\ArcheryGround\RemoveTarget;
use App\Application\Command\ArcheryGround\RemoveTargetHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DeleteTargetController extends AbstractController
{
    public function __construct(private readonly RemoveTargetHandler $removeTargetHandler)
    {
    }

    #[Route('/archery-grounds/{id}/targets/{targetId}/delete', name: 'archery_ground_delete_target', methods: ['POST'])]
    public function __invoke(string $id, string $targetId): Response
    {
        $result = ($this->removeTargetHandler)(new RemoveTarget(
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
