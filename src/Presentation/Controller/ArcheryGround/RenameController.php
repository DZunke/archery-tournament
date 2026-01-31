<?php

declare(strict_types=1);

namespace App\Presentation\Controller\ArcheryGround;

use App\Application\Command\ArcheryGround\RenameArcheryGround;
use App\Application\Command\ArcheryGround\RenameArcheryGroundHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class RenameController extends AbstractController
{
    public function __construct(private readonly RenameArcheryGroundHandler $renameArcheryGroundHandler)
    {
    }

    #[Route('/archery-grounds/{id}/rename', name: 'archery_ground_rename', methods: ['POST'])]
    public function __invoke(Request $request, string $id): Response
    {
        $result = ($this->renameArcheryGroundHandler)(new RenameArcheryGround(
            id: $id,
            name: (string) $request->request->get('name', ''),
        ));

        if ($result->success) {
            $this->addFlash('success', (string) $result->message);
        } else {
            $this->addFlash('error', (string) $result->message);
        }

        return $this->redirectToRoute('archery_ground_show', ['id' => $id]);
    }
}
