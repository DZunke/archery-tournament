<?php

declare(strict_types=1);

namespace App\Presentation\Controller\Tournament;

use App\Application\Bus\QueryBus;
use App\Application\Query\ArcheryGround\ListArcheryGrounds;
use App\Application\Query\Tournament\ListTournaments;
use App\Domain\Entity\Tournament;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

use function array_filter;
use function array_values;
use function count;
use function is_countable;
use function stripos;
use function trim;

final class IndexController extends AbstractController
{
    public function __construct(private readonly QueryBus $queryBus)
    {
    }

    #[Route('/tournaments', name: 'tournament_index', methods: ['GET'])]
    public function __invoke(Request $request): Response
    {
        $tournaments = $this->queryBus->ask(new ListTournaments());
        $grounds     = $this->queryBus->ask(new ListArcheryGrounds());

        $nameFilter          = trim($request->query->get('name', ''));
        $groundFilter        = $request->query->get('archery_ground_id', '');
        $dateFilter          = $request->query->get('date', '');
        $filteredCount       = 0;
        $filteredTournaments = array_values(array_filter(
            $tournaments,
            static function (Tournament $tournament) use (
                $nameFilter,
                $groundFilter,
                $dateFilter,
                &$filteredCount,
            ): bool {
                if ($nameFilter !== '' && stripos($tournament->name(), $nameFilter) === false) {
                    return false;
                }

                if ($groundFilter !== '' && $tournament->archeryGround()->id() !== $groundFilter) {
                    return false;
                }

                if ($dateFilter !== '' && $tournament->eventDate()->format('Y-m-d') !== $dateFilter) {
                    return false;
                }

                $filteredCount++;

                return true;
            },
        ));

        return $this->render('tournament/index.html.twig', [
            'tournaments' => $filteredTournaments,
            'grounds' => $grounds,
            'nameFilter' => $nameFilter,
            'groundFilter' => $groundFilter,
            'dateFilter' => $dateFilter,
            'totalCount' => is_countable($tournaments) ? count($tournaments) : 0,
            'filteredCount' => $filteredCount,
        ]);
    }
}
