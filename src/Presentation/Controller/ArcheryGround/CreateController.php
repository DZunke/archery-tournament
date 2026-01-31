<?php

declare(strict_types=1);

namespace App\Presentation\Controller\ArcheryGround;

use App\Application\Bus\CommandBus;
use App\Presentation\Input\ArcheryGround\CreateArcheryGroundInput;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CreateController extends AbstractController
{
    public function __construct(private readonly CommandBus $commandBus)
    {
    }

    #[Route('/archery-grounds/new', name: 'archery_ground_new', methods: ['GET', 'POST'])]
    public function __invoke(Request $request): Response
    {
        $error = null;

        if ($request->isMethod('POST')) {
            $input  = CreateArcheryGroundInput::fromRequest($request);
            $errors = $input->errors();
            if ($errors !== []) {
                $error = $errors[0];
            } else {
                $result = $this->commandBus->dispatch($input->toCommand());

                if ($result->success) {
                    $this->addFlash('success', (string) $result->message);

                    return $this->redirectToRoute('archery_ground_show', ['id' => $result->data['id']]);
                }

                $error = $result->message;
            }
        }

        return $this->render('archery_ground/new.html.twig', ['error' => $error]);
    }
}
