<?php

declare(strict_types=1);

namespace App\Presentation\Controller\Tournament;

use App\Application\Bus\CommandBus;
use App\Presentation\Input\Tournament\CreateTournamentInput;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CreateController extends AbstractController
{
    public function __construct(private readonly CommandBus $commandBus)
    {
    }

    #[Route('/tournaments', name: 'tournament_create', methods: ['POST'])]
    public function __invoke(Request $request): Response
    {
        $token = (string) $request->request->get('_token');
        if (! $this->isCsrfTokenValid('tournament_create', $token)) {
            $this->addFlash('error', 'Invalid CSRF token.');

            return $this->redirectToRoute('tournament_new');
        }

        $input  = CreateTournamentInput::fromRequest($request);
        $errors = $input->errors();

        if ($errors !== []) {
            foreach ($errors as $error) {
                $this->addFlash('error', $error);
            }

            return $this->redirectToRoute('tournament_new');
        }

        $result = $this->commandBus->dispatch($input->toCommand());

        if (! $result->success) {
            $this->addFlash('error', (string) $result->message);

            return $this->redirectToRoute('tournament_new');
        }

        $this->addFlash('success', (string) $result->message);

        return $this->redirectToRoute('tournament_show', ['id' => $result->data['id']]);
    }
}
