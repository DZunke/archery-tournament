<?php

declare(strict_types=1);

namespace App\Presentation\Controller\Tournament;

use App\Application\Bus\CommandBus;
use App\Application\Command\Tournament\DeleteTournament;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DeleteController extends AbstractController
{
    public function __construct(private readonly CommandBus $commandBus)
    {
    }

    #[Route('/tournaments/{id}/delete', name: 'tournament_delete', methods: ['POST'])]
    public function __invoke(string $id): Response
    {
        $result = $this->commandBus->dispatch(new DeleteTournament($id));

        if ($result->success) {
            $this->addFlash('success', (string) $result->message);
        } else {
            $this->addFlash('error', (string) $result->message);
        }

        return $this->redirectToRoute('tournament_index');
    }
}
