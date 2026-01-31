<?php

declare(strict_types=1);

namespace App\Presentation\Controller\ArcheryGround;

use App\Application\Bus\CommandBus;
use App\Presentation\Input\ArcheryGround\RenameArcheryGroundInput;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class RenameController extends AbstractController
{
    public function __construct(private readonly CommandBus $commandBus)
    {
    }

    #[Route('/archery-grounds/{id}/rename', name: 'archery_ground_rename', methods: ['POST'])]
    public function __invoke(Request $request, string $id): Response
    {
        $token = (string) $request->request->get('_token');
        if (! $this->isCsrfTokenValid('archery_ground_rename_' . $id, $token)) {
            $this->addFlash('error', 'Invalid CSRF token.');

            return $this->redirectToRoute('archery_ground_show', ['id' => $id]);
        }

        $input  = RenameArcheryGroundInput::fromRequest($request);
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
