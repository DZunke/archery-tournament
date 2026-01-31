<?php

declare(strict_types=1);

namespace App\Presentation\Controller\ArcheryGround;

use App\Application\Command\ArcheryGround\DeleteArcheryGround;
use App\Application\Command\ArcheryGround\DeleteArcheryGroundHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DeleteController extends AbstractController
{
    public function __construct(private readonly DeleteArcheryGroundHandler $deleteArcheryGroundHandler)
    {
    }

    #[Route('/archery-grounds/{id}/delete', name: 'archery_ground_delete', methods: ['POST'])]
    public function __invoke(string $id): Response
    {
        $result = ($this->deleteArcheryGroundHandler)(new DeleteArcheryGround($id));

        if ($result->success) {
            $this->addFlash('success', (string) $result->message);
        } else {
            $this->addFlash('error', (string) $result->message);
        }

        return $this->redirectToRoute('archery_ground_index');
    }
}
