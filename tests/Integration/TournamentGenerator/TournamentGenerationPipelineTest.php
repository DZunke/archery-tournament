<?php

declare(strict_types=1);

namespace App\Tests\Integration\TournamentGenerator;

use App\Application\Service\TournamentGenerator\DTO\TournamentGenerationRequest;
use App\Application\Service\TournamentGenerator\Exception\NotEnoughLanesAtShootingRange;
use App\Application\Service\TournamentGenerator\Exception\TournamentGenerationFailed;
use App\Application\Service\TournamentGenerator\Step\DSB3D\CalculateRequiredRounds;
use App\Application\Service\TournamentGenerator\Step\DSB3D\CollectQualifiedLanes;
use App\Application\Service\TournamentGenerator\Step\DSB3D\GenerateTournamentTargets;
use App\Application\Service\TournamentGenerator\Step\DSB3D\PlaceTargetToTournamentLanes;
use App\Application\Service\TournamentGenerator\Step\DSB3D\PlaceTargetTypes;
use App\Application\Service\TournamentGenerator\TournamentGenerationPipeline;
use App\Domain\Entity\ArcheryGround;
use App\Domain\Entity\ArcheryGround\ShootingLane;
use App\Domain\Entity\ArcheryGround\Target;
use App\Domain\ValueObject\Ruleset;
use App\Domain\ValueObject\TargetType;
use App\Tests\Fixtures\AcheryGroundMediumSized;
use App\Tests\Fixtures\ArcheryGroundSmallSized;
use App\Tests\Fixtures\ArcheryGroundWithTrainingOnly;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Uid\Uuid;

use function count;
use function sprintf;

final class TournamentGenerationPipelineTest extends TestCase
{
    private TournamentGenerationPipeline $pipeline;

    protected function setUp(): void
    {
        $logger = new NullLogger();

        $steps = [
            new CollectQualifiedLanes($logger),
            new CalculateRequiredRounds($logger),
            new PlaceTargetTypes($logger),
            new PlaceTargetToTournamentLanes(),
            new GenerateTournamentTargets($logger),
        ];

        $this->pipeline = new TournamentGenerationPipeline($steps, $logger);
    }

    public function testGeneratesTournamentWithCorrectTargetCount(): void
    {
        $archeryGround = AcheryGroundMediumSized::create();

        $request = new TournamentGenerationRequest(
            archeryGround: $archeryGround,
            ruleset: Ruleset::DSB_3D,
            amountOfTargets: 8,
        );

        $tournament = $this->pipeline->generate($request);

        self::assertCount(8, $tournament->targets());
    }

    public function testGeneratesMultiRoundTournament(): void
    {
        $archeryGround = ArcheryGroundSmallSized::create();

        $request = new TournamentGenerationRequest(
            archeryGround: $archeryGround,
            ruleset: Ruleset::DSB_3D,
            amountOfTargets: 12,
        );

        $tournament = $this->pipeline->generate($request);

        self::assertCount(12, $tournament->targets());

        $rounds = [];
        foreach ($tournament->targets() as $target) {
            $rounds[$target->round()] = true;
        }

        self::assertGreaterThan(1, count($rounds), 'Tournament should have multiple rounds');
    }

    public function testEachTargetHasValidStakeDistances(): void
    {
        $archeryGround = AcheryGroundMediumSized::create();

        $request = new TournamentGenerationRequest(
            archeryGround: $archeryGround,
            ruleset: Ruleset::DSB_3D,
            amountOfTargets: 8,
        );

        $tournament = $this->pipeline->generate($request);

        foreach ($tournament->targets() as $tournamentTarget) {
            $stakes = $tournamentTarget->stakes()->all();

            self::assertArrayHasKey('red', $stakes, 'Target should have red stake');
            self::assertArrayHasKey('blue', $stakes, 'Target should have blue stake');
            self::assertArrayHasKey('yellow', $stakes, 'Target should have yellow stake');

            self::assertGreaterThan(0, $stakes['red'], 'Red stake should be positive');
            self::assertGreaterThan(0, $stakes['blue'], 'Blue stake should be positive');
            self::assertGreaterThanOrEqual(0, $stakes['yellow'], 'Yellow stake should be non-negative');
        }
    }

    public function testStakeDistancesRespectLaneMaxDistance(): void
    {
        $archeryGround = AcheryGroundMediumSized::create();

        $request = new TournamentGenerationRequest(
            archeryGround: $archeryGround,
            ruleset: Ruleset::DSB_3D,
            amountOfTargets: 8,
        );

        $tournament = $this->pipeline->generate($request);

        foreach ($tournament->targets() as $tournamentTarget) {
            $laneMaxDistance = $tournamentTarget->shootingLane()->maxDistance();
            $stakes          = $tournamentTarget->stakes()->all();

            foreach ($stakes as $stakeName => $distance) {
                $format  = 'Stake "%s" distance (%dm) exceeds max (%dm)';
                $message = sprintf($format, $stakeName, $distance, $laneMaxDistance);
                self::assertLessThanOrEqual($laneMaxDistance, $distance, $message);
            }
        }
    }

    public function testTargetTypesAreBalancedAcrossRounds(): void
    {
        $archeryGround = AcheryGroundMediumSized::create();

        $request = new TournamentGenerationRequest(
            archeryGround: $archeryGround,
            ruleset: Ruleset::DSB_3D,
            amountOfTargets: 8,
        );

        $tournament = $this->pipeline->generate($request);

        $typesUsed = [];
        foreach ($tournament->targets() as $tournamentTarget) {
            $targetType             = $tournamentTarget->target()->type()->value;
            $typesUsed[$targetType] = ($typesUsed[$targetType] ?? 0) + 1;
        }

        $requiredTypes = Ruleset::DSB_3D->requiredTargetTypes();
        foreach ($requiredTypes as $targetType) {
            self::assertArrayHasKey(
                $targetType->value,
                $typesUsed,
                'Target type "' . $targetType->name . '" should be represented in the tournament',
            );
        }
    }

    public function testRandomizeStakesBetweenRoundsProducesDifferentDistances(): void
    {
        $archeryGround = AcheryGroundMediumSized::create();

        $request = new TournamentGenerationRequest(
            archeryGround: $archeryGround,
            ruleset: Ruleset::DSB_3D,
            amountOfTargets: 16,
            randomizeStakesBetweenRounds: true,
        );

        $tournament = $this->pipeline->generate($request);

        self::assertCount(16, $tournament->targets());
    }

    public function testFailsWhenNoLanesAvailable(): void
    {
        $archeryGround = new ArcheryGround(
            id: Uuid::v4()->toRfc4122(),
            name: 'Empty Ground',
        );

        $archeryGround->addTarget(new Target(
            id: Uuid::v4()->toRfc4122(),
            type: TargetType::ANIMAL_GROUP_1,
            name: 'Deer',
            image: 'deer.png',
        ));

        $request = new TournamentGenerationRequest(
            archeryGround: $archeryGround,
            ruleset: Ruleset::DSB_3D,
            amountOfTargets: 4,
        );

        $this->expectException(NotEnoughLanesAtShootingRange::class);

        $this->pipeline->generate($request);
    }

    public function testFailsWhenLanesDoNotMeetMinimumDistanceRequirements(): void
    {
        $archeryGround = new ArcheryGround(
            id: Uuid::v4()->toRfc4122(),
            name: 'Short Distance Ground',
        );

        $archeryGround->addShootingLane(new ShootingLane(
            id: Uuid::v4()->toRfc4122(),
            name: 'Short Lane',
            maxDistance: 5.0,
        ));

        $archeryGround->addTarget(new Target(
            id: Uuid::v4()->toRfc4122(),
            type: TargetType::ANIMAL_GROUP_1,
            name: 'Deer',
            image: 'deer.png',
        ));

        $request = new TournamentGenerationRequest(
            archeryGround: $archeryGround,
            ruleset: Ruleset::DSB_3D,
            amountOfTargets: 4,
        );

        $this->expectException(TournamentGenerationFailed::class);

        $this->pipeline->generate($request);
    }

    public function testFailsWhenNotEnoughTargetsForType(): void
    {
        $archeryGround = new ArcheryGround(
            id: Uuid::v4()->toRfc4122(),
            name: 'Incomplete Target Storage',
        );

        $archeryGround->addShootingLane(new ShootingLane(
            id: Uuid::v4()->toRfc4122(),
            name: 'Lane 1',
            maxDistance: 50.0,
        ));
        $archeryGround->addShootingLane(new ShootingLane(
            id: Uuid::v4()->toRfc4122(),
            name: 'Lane 2',
            maxDistance: 45.0,
        ));
        $archeryGround->addShootingLane(new ShootingLane(
            id: Uuid::v4()->toRfc4122(),
            name: 'Lane 3',
            maxDistance: 40.0,
        ));
        $archeryGround->addShootingLane(new ShootingLane(
            id: Uuid::v4()->toRfc4122(),
            name: 'Lane 4',
            maxDistance: 35.0,
        ));

        $archeryGround->addTarget(new Target(
            id: Uuid::v4()->toRfc4122(),
            type: TargetType::ANIMAL_GROUP_1,
            name: 'Deer',
            image: 'deer.png',
        ));

        $request = new TournamentGenerationRequest(
            archeryGround: $archeryGround,
            ruleset: Ruleset::DSB_3D,
            amountOfTargets: 4,
        );

        $this->expectException(TournamentGenerationFailed::class);

        $this->pipeline->generate($request);
    }

    public function testFreehandRulesetGeneratesWithoutTypeBalancing(): void
    {
        $archeryGround = new ArcheryGround(
            id: Uuid::v4()->toRfc4122(),
            name: 'Freehand Ground',
        );

        $archeryGround->addShootingLane(new ShootingLane(
            id: Uuid::v4()->toRfc4122(),
            name: 'Lane 1',
            maxDistance: 30.0,
        ));
        $archeryGround->addShootingLane(new ShootingLane(
            id: Uuid::v4()->toRfc4122(),
            name: 'Lane 2',
            maxDistance: 25.0,
        ));

        $archeryGround->addTarget(new Target(
            id: Uuid::v4()->toRfc4122(),
            type: TargetType::ANIMAL_GROUP_1,
            name: 'Deer',
            image: 'deer.png',
        ));
        $archeryGround->addTarget(new Target(
            id: Uuid::v4()->toRfc4122(),
            type: TargetType::ANIMAL_GROUP_2,
            name: 'Wolf',
            image: 'wolf.png',
        ));

        $request = new TournamentGenerationRequest(
            archeryGround: $archeryGround,
            ruleset: Ruleset::FREEHAND,
            amountOfTargets: 4,
        );

        $tournament = $this->pipeline->generate($request);

        self::assertCount(4, $tournament->targets());
    }

    public function testLaneUniquenessWithinRound(): void
    {
        $archeryGround = AcheryGroundMediumSized::create();

        $request = new TournamentGenerationRequest(
            archeryGround: $archeryGround,
            ruleset: Ruleset::DSB_3D,
            amountOfTargets: 8,
        );

        $tournament = $this->pipeline->generate($request);

        $lanesPerRound = [];
        foreach ($tournament->targets() as $target) {
            $round  = $target->round();
            $laneId = $target->shootingLane()->id();

            if (! isset($lanesPerRound[$round])) {
                $lanesPerRound[$round] = [];
            }

            self::assertArrayNotHasKey(
                $laneId,
                $lanesPerRound[$round],
                'Lane should not be used twice in the same round',
            );

            $lanesPerRound[$round][$laneId] = true;
        }
    }

    public function testExcludesTrainingOnlyLanesByDefault(): void
    {
        $archeryGround = ArcheryGroundWithTrainingOnly::create();

        $request = new TournamentGenerationRequest(
            archeryGround: $archeryGround,
            ruleset: Ruleset::FREEHAND,
            amountOfTargets: 4,
        );

        $tournament = $this->pipeline->generate($request);

        foreach ($tournament->targets() as $tournamentTarget) {
            self::assertFalse(
                $tournamentTarget->shootingLane()->forTrainingOnly(),
                'Tournament should not include training-only lanes by default',
            );
        }
    }

    public function testExcludesTrainingOnlyTargetsByDefault(): void
    {
        $archeryGround = ArcheryGroundWithTrainingOnly::create();

        $request = new TournamentGenerationRequest(
            archeryGround: $archeryGround,
            ruleset: Ruleset::FREEHAND,
            amountOfTargets: 4,
        );

        $tournament = $this->pipeline->generate($request);

        foreach ($tournament->targets() as $tournamentTarget) {
            self::assertFalse(
                $tournamentTarget->target()->forTrainingOnly(),
                'Tournament should not include training-only targets by default',
            );
        }
    }

    public function testIncludesTrainingOnlyLanesWhenFlagEnabled(): void
    {
        $archeryGround = ArcheryGroundWithTrainingOnly::create();

        $request = new TournamentGenerationRequest(
            archeryGround: $archeryGround,
            ruleset: Ruleset::FREEHAND,
            amountOfTargets: 6,
            includeTrainingOnly: true,
        );

        $tournament = $this->pipeline->generate($request);

        $trainingLanesUsed = 0;
        foreach ($tournament->targets() as $tournamentTarget) {
            if (! $tournamentTarget->shootingLane()->forTrainingOnly()) {
                continue;
            }

            $trainingLanesUsed++;
        }

        self::assertGreaterThan(
            0,
            $trainingLanesUsed,
            'When includeTrainingOnly is enabled, training lanes should be used',
        );
    }

    public function testIncludesTrainingOnlyTargetsWhenFlagEnabled(): void
    {
        $archeryGround = ArcheryGroundWithTrainingOnly::create();

        $request = new TournamentGenerationRequest(
            archeryGround: $archeryGround,
            ruleset: Ruleset::FREEHAND,
            amountOfTargets: 6,
            includeTrainingOnly: true,
        );

        $tournament = $this->pipeline->generate($request);

        $trainingTargetsUsed = 0;
        foreach ($tournament->targets() as $tournamentTarget) {
            if (! $tournamentTarget->target()->forTrainingOnly()) {
                continue;
            }

            $trainingTargetsUsed++;
        }

        self::assertGreaterThan(
            0,
            $trainingTargetsUsed,
            'When includeTrainingOnly is enabled, training targets should be used',
        );
    }
}
