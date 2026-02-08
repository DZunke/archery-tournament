<?php

declare(strict_types=1);

namespace App\Presentation\Controller\Tournament;

use App\Application\Bus\CommandBus;
use App\Application\Command\Tournament\RemoveAttachment;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DeleteAttachmentController extends AbstractController
{
    public function __construct(private readonly CommandBus $commandBus)
    {
    }

    #[Route('/tournaments/{id}/attachments/{attachmentId}', name: 'tournament_delete_attachment', methods: ['POST'])]
    public function __invoke(Request $request, string $id, string $attachmentId): Response
    {
        $token = (string) $request->request->get('_token');
        if (! $this->isCsrfTokenValid('tournament_delete_attachment_' . $id . '_' . $attachmentId, $token)) {
            $this->addFlash('error', 'Invalid CSRF token.');

            return $this->redirectToRoute('tournament_show', ['id' => $id]);
        }

        $result = $this->commandBus->dispatch(new RemoveAttachment($id, $attachmentId));

        if ($result->success) {
            $this->addFlash('success', (string) $result->message);
        } else {
            $this->addFlash('error', (string) $result->message);
        }

        return $this->redirectToRoute('tournament_show', ['id' => $id]);
    }
}
