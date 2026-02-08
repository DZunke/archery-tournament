<?php

declare(strict_types=1);

namespace App\Tests\Unit\Command\ArcheryGround;

use App\Application\Command\ArcheryGround\RemoveShootingLane;
use App\Application\Command\ArcheryGround\RemoveShootingLaneHandler;
use App\Domain\Entity\ArcheryGround;
use App\Domain\Entity\ArcheryGround\ShootingLane;
use App\Domain\Entity\ArcheryGround\Target;
use App\Domain\Entity\Tournament;
use App\Domain\Entity\TournamentTarget;
use App\Domain\ValueObject\Ruleset;
use App\Domain\ValueObject\StakeDistances;
use App\Domain\ValueObject\TargetType;
use App\Tests\Unit\Support\InMemoryArcheryGroundRepository;
use App\Tests\Unit\Support\InMemoryTournamentRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

#[CoversClass(RemoveShootingLaneHandler::class)]
final class RemoveShootingLaneHandlerTest extends TestCase
{
    #[Test]
    public function removesShootingLane(): void
    {
        $archeryGroundRepository = new InMemoryArcheryGroundRepository();
        $tournamentRepository    = new InMemoryTournamentRepository();
        $handler                 = new RemoveShootingLaneHandler($archeryGroundRepository, $tournamentRepository);

        $groundId = Uuid::v4()->toRfc4122();
        $laneId   = Uuid::v4()->toRfc4122();

        $ground = new ArcheryGround($groundId, 'Forest Range');
        $ground->addShootingLane(new ShootingLane($laneId, 'Lane North', 30.0));
        $archeryGroundRepository->seed($ground);

        $result = $handler(new RemoveShootingLane($groundId, $laneId));

        self::assertTrue($result->success);
        self::assertSame('The shooting lane "Lane North" was removed.', $result->message);
        self::assertSame([$laneId], $archeryGroundRepository->removedLanes);
    }

    #[Test]
    public function failsWhenLaneIsUsedInTournament(): void
    {
        $archeryGroundRepository = new InMemoryArcheryGroundRepository();
        $tournamentRepository    = new InMemoryTournamentRepository();
        $handler                 = new RemoveShootingLaneHandler($archeryGroundRepository, $tournamentRepository);

        $groundId = Uuid::v4()->toRfc4122();
        $laneId   = Uuid::v4()->toRfc4122();
        $targetId = Uuid::v4()->toRfc4122();

        $lane   = new ShootingLane($laneId, 'Lane North', 30.0);
        $target = new Target($targetId, TargetType::ANIMAL_GROUP_1, 'Deer', '/uploads/deer.jpg');

        $ground = new ArcheryGround($groundId, 'Forest Range');
        $ground->addShootingLane($lane);
        $ground->addTarget($target);
        $archeryGroundRepository->seed($ground);

        $tournament = Tournament::create('Spring Championship', Ruleset::DSB_3D, $ground, 12);
        $tournament->addTarget(new TournamentTarget(
            round: 1,
            shootingLane: $lane,
            target: $target,
            distance: 20,
            stakes: new StakeDistances(['red' => 15, 'blue' => 20]),
        ));
        $tournamentRepository->seed($tournament);

        $result = $handler(new RemoveShootingLane($groundId, $laneId));

        self::assertFalse($result->success);
        self::assertNotNull($result->message);
        self::assertStringContainsString('Cannot remove lane', $result->message);
        self::assertStringContainsString('Spring Championship', $result->message);
        self::assertSame([], $archeryGroundRepository->removedLanes);
    }
}
