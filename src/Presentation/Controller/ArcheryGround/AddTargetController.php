<?php

declare(strict_types=1);

namespace App\Presentation\Controller\ArcheryGround;

use App\Application\Bus\CommandBus;
use App\Presentation\Input\ArcheryGround\AddTargetInput;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AddTargetController extends AbstractController
{
    public function __construct(private readonly CommandBus $commandBus)
    {
    }

    #[Route('/archery-grounds/{id}/targets', name: 'archery_ground_add_target', methods: ['POST'])]
    public function __invoke(Request $request, string $id): Response
    {
        $input  = AddTargetInput::fromRequest($request);
        $errors = $input->errors();
        if ($errors !== []) {
            foreach ($errors as $error) {
                $this->addFlash('error', $error);
            }

            return $this->redirectToRoute('archery_ground_show', ['id' => $id]);
        }

        $result = $this->commandBus->dispatch($input->toCommand($id));

        if ($result->success) {
            $this->addFlash('success', (string) $result->message);
        } else {
            $this->addFlash('error', (string) $result->message);
        }

        return $this->redirectToRoute('archery_ground_show', ['id' => $id]);
    }
}
