<?php

declare(strict_types=1);

namespace App\Presentation\Controller\ArcheryGround;

use App\Application\Command\ArcheryGround\AddTarget;
use App\Application\Command\ArcheryGround\AddTargetHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AddTargetController extends AbstractController
{
    public function __construct(private readonly AddTargetHandler $addTargetHandler)
    {
    }

    #[Route('/archery-grounds/{id}/targets', name: 'archery_ground_add_target', methods: ['POST'])]
    public function __invoke(Request $request, string $id): Response
    {
        $result = ($this->addTargetHandler)(new AddTarget(
            archeryGroundId: $id,
            type: (string) $request->request->get('type', ''),
            name: (string) $request->request->get('name', ''),
            image: $request->files->get('image'),
        ));

        if ($result->success) {
            $this->addFlash('success', (string) $result->message);
        } else {
            $this->addFlash('error', (string) $result->message);
        }

        return $this->redirectToRoute('archery_ground_show', ['id' => $id]);
    }
}
