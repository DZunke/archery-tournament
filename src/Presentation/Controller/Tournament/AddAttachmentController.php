<?php

declare(strict_types=1);

namespace App\Presentation\Controller\Tournament;

use App\Application\Bus\CommandBus;
use App\Presentation\Input\Tournament\AddAttachmentInput;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AddAttachmentController extends AbstractController
{
    public function __construct(private readonly CommandBus $commandBus)
    {
    }

    #[Route('/tournaments/{id}/attachments', name: 'tournament_add_attachment', methods: ['POST'])]
    public function __invoke(Request $request, string $id): Response
    {
        $token = (string) $request->request->get('_token');
        if (! $this->isCsrfTokenValid('tournament_add_attachment_' . $id, $token)) {
            $this->addFlash('error', 'Invalid CSRF token.');

            return $this->redirectToRoute('tournament_show', ['id' => $id]);
        }

        $input  = AddAttachmentInput::fromRequest($request);
        $errors = $input->errors();
        if ($errors !== []) {
            foreach ($errors as $error) {
                $this->addFlash('error', $error);
            }

            return $this->redirectToRoute('tournament_show', ['id' => $id]);
        }

        $result = $this->commandBus->dispatch($input->toCommand($id));

        if ($result->success) {
            $this->addFlash('success', (string) $result->message);
        } else {
            $this->addFlash('error', (string) $result->message);
        }

        return $this->redirectToRoute('tournament_show', ['id' => $id]);
    }
}
