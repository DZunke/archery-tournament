<?php

declare(strict_types=1);

namespace App\Tests\Unit\Command\ArcheryGround;

use App\Application\Command\ArcheryGround\RemoveTarget;
use App\Application\Command\ArcheryGround\RemoveTargetHandler;
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
use App\Tests\Unit\Support\SpyTargetImageStorage;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

#[CoversClass(RemoveTargetHandler::class)]
final class RemoveTargetHandlerTest extends TestCase
{
    #[Test]
    public function removesTargetImageAndRecord(): void
    {
        $archeryGroundRepository = new InMemoryArcheryGroundRepository();
        $tournamentRepository    = new InMemoryTournamentRepository();
        $storage                 = new SpyTargetImageStorage();
        $handler                 = new RemoveTargetHandler($archeryGroundRepository, $tournamentRepository, $storage);

        $groundId = Uuid::v4()->toRfc4122();
        $targetId = Uuid::v4()->toRfc4122();

        $ground = new ArcheryGround($groundId, 'Main Ground');
        $ground->addTarget(new Target(
            id: $targetId,
            type: TargetType::ANIMAL_GROUP_1,
            name: 'Deer',
            image: '/uploads/targets/deer.png',
        ));

        $archeryGroundRepository->seed($ground);

        $result = $handler(new RemoveTarget($groundId, $targetId));

        self::assertTrue($result->success);
        self::assertSame(['/uploads/targets/deer.png'], $storage->removed);
        self::assertSame([$targetId], $archeryGroundRepository->removedTargets);
    }

    #[Test]
    public function failsWhenTargetIsUsedInTournament(): void
    {
        $archeryGroundRepository = new InMemoryArcheryGroundRepository();
        $tournamentRepository    = new InMemoryTournamentRepository();
        $storage                 = new SpyTargetImageStorage();
        $handler                 = new RemoveTargetHandler($archeryGroundRepository, $tournamentRepository, $storage);

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

        $result = $handler(new RemoveTarget($groundId, $targetId));

        self::assertFalse($result->success);
        self::assertNotNull($result->message);
        self::assertStringContainsString('Cannot remove target', $result->message);
        self::assertStringContainsString('Spring Championship', $result->message);
        self::assertSame([], $storage->removed);
        self::assertSame([], $archeryGroundRepository->removedTargets);
    }
}
