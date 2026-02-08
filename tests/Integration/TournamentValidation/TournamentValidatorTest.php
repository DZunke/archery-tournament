<?php

declare(strict_types=1);

namespace App\Tests\Integration\TournamentValidation;

use App\Application\Service\TournamentValidation\Rule\AssignmentReferenceRule;
use App\Application\Service\TournamentValidation\Rule\LaneTargetConsistencyRule;
use App\Application\Service\TournamentValidation\Rule\LaneUniquenessRule;
use App\Application\Service\TournamentValidation\Rule\StakeDistanceRule;
use App\Application\Service\TournamentValidation\Rule\TargetCountRule;
use App\Application\Service\TournamentValidation\Rule\TargetGroupBalanceRule;
use App\Application\Service\TournamentValidation\Rule\TargetUniquenessRule;
use App\Application\Service\TournamentValidation\TournamentValidationAssignment;
use App\Application\Service\TournamentValidation\TournamentValidationContext;
use App\Application\Service\TournamentValidation\TournamentValidationIssue;
use App\Application\Service\TournamentValidation\TournamentValidator;
use App\Domain\Entity\ArcheryGround\ShootingLane;
use App\Domain\Entity\ArcheryGround\Target;
use App\Domain\ValueObject\Ruleset;
use App\Domain\ValueObject\TargetType;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

use function array_filter;
use function array_values;
use function implode;

final class TournamentValidatorTest extends TestCase
{
    private TournamentValidator $validator;

    protected function setUp(): void
    {
        $rules = [
            new AssignmentReferenceRule(),
            new StakeDistanceRule(),
            new TargetUniquenessRule(),
            new LaneUniquenessRule(),
            new LaneTargetConsistencyRule(),
            new TargetCountRule(),
            new TargetGroupBalanceRule(),
        ];

        $this->validator = new TournamentValidator($rules);
    }

    public function testValidTournamentPassesAllRules(): void
    {
        $lane1 = new ShootingLane(Uuid::v4()->toRfc4122(), 'Lane 1', 50.0);
        $lane2 = new ShootingLane(Uuid::v4()->toRfc4122(), 'Lane 2', 45.0);
        $lane3 = new ShootingLane(Uuid::v4()->toRfc4122(), 'Lane 3', 35.0);
        $lane4 = new ShootingLane(Uuid::v4()->toRfc4122(), 'Lane 4', 25.0);

        $target1 = new Target(Uuid::v4()->toRfc4122(), TargetType::ANIMAL_GROUP_1, 'Deer', 'deer.png');
        $target2 = new Target(Uuid::v4()->toRfc4122(), TargetType::ANIMAL_GROUP_2, 'Wolf', 'wolf.png');
        $target3 = new Target(Uuid::v4()->toRfc4122(), TargetType::ANIMAL_GROUP_3, 'Hare', 'hare.png');
        $target4 = new Target(Uuid::v4()->toRfc4122(), TargetType::ANIMAL_GROUP_4, 'Raven', 'raven.png');

        $assignments = [
            new TournamentValidationAssignment(
                round: 1,
                lane: $lane1,
                target: $target1,
                stakes: ['red' => 40, 'blue' => 25, 'yellow' => 15],
            ),
            new TournamentValidationAssignment(
                round: 1,
                lane: $lane2,
                target: $target2,
                stakes: ['red' => 30, 'blue' => 20, 'yellow' => 12],
            ),
            new TournamentValidationAssignment(
                round: 1,
                lane: $lane3,
                target: $target3,
                stakes: ['red' => 20, 'blue' => 15, 'yellow' => 7],
            ),
            new TournamentValidationAssignment(
                round: 1,
                lane: $lane4,
                target: $target4,
                stakes: ['red' => 10, 'blue' => 8, 'yellow' => 5],
            ),
        ];

        $context = new TournamentValidationContext(
            ruleset: Ruleset::DSB_3D,
            expectedTargetCount: 4,
            assignments: $assignments,
        );

        $result = $this->validator->validate($context);

        $formattedIssues = $this->formatIssues($result->issues());
        self::assertTrue($result->isValid(), 'Valid tournament should pass all rules: ' . $formattedIssues);
    }

    public function testDetectsDuplicateLaneInSameRound(): void
    {
        $lane1   = new ShootingLane(Uuid::v4()->toRfc4122(), 'Lane 1', 50.0);
        $target1 = new Target(Uuid::v4()->toRfc4122(), TargetType::ANIMAL_GROUP_1, 'Deer', 'deer.png');
        $target2 = new Target(Uuid::v4()->toRfc4122(), TargetType::ANIMAL_GROUP_2, 'Wolf', 'wolf.png');

        $assignments = [
            new TournamentValidationAssignment(
                round: 1,
                lane: $lane1,
                target: $target1,
                stakes: ['red' => 40, 'blue' => 25, 'yellow' => 15],
                row: 1,
            ),
            new TournamentValidationAssignment(
                round: 1,
                lane: $lane1,
                target: $target2,
                stakes: ['red' => 30, 'blue' => 20, 'yellow' => 12],
                row: 2,
            ),
        ];

        $context = new TournamentValidationContext(
            ruleset: Ruleset::DSB_3D,
            expectedTargetCount: 2,
            assignments: $assignments,
        );

        $result = $this->validator->validate($context);

        self::assertFalse($result->isValid());
        self::assertNotEmpty($this->findIssuesByRule($result->issues(), 'Lane Uniqueness'));
    }

    public function testAllowsSameLaneInDifferentRounds(): void
    {
        $lane1   = new ShootingLane(Uuid::v4()->toRfc4122(), 'Lane 1', 50.0);
        $target1 = new Target(Uuid::v4()->toRfc4122(), TargetType::ANIMAL_GROUP_1, 'Deer', 'deer.png');

        $assignments = [
            new TournamentValidationAssignment(
                round: 1,
                lane: $lane1,
                target: $target1,
                stakes: ['red' => 40, 'blue' => 25, 'yellow' => 15],
            ),
            new TournamentValidationAssignment(
                round: 2,
                lane: $lane1,
                target: $target1,
                stakes: ['red' => 40, 'blue' => 25, 'yellow' => 15],
            ),
        ];

        $context = new TournamentValidationContext(
            ruleset: Ruleset::FREEHAND,
            expectedTargetCount: 2,
            assignments: $assignments,
        );

        $result = $this->validator->validate($context);

        $laneUniquenessIssues = $this->findIssuesByRule($result->issues(), 'Lane Uniqueness');
        self::assertEmpty($laneUniquenessIssues, 'Same lane in different rounds should be allowed');
    }

    public function testDetectsIncorrectTargetCount(): void
    {
        $lane1   = new ShootingLane(Uuid::v4()->toRfc4122(), 'Lane 1', 50.0);
        $target1 = new Target(Uuid::v4()->toRfc4122(), TargetType::ANIMAL_GROUP_1, 'Deer', 'deer.png');

        $assignments = [
            new TournamentValidationAssignment(
                round: 1,
                lane: $lane1,
                target: $target1,
                stakes: ['red' => 40, 'blue' => 25, 'yellow' => 15],
            ),
        ];

        $context = new TournamentValidationContext(
            ruleset: Ruleset::FREEHAND,
            expectedTargetCount: 4,
            assignments: $assignments,
        );

        $result = $this->validator->validate($context);

        self::assertFalse($result->isValid());
        $countIssues = $this->findIssuesByRule($result->issues(), 'Target Count');
        self::assertNotEmpty($countIssues);
    }

    public function testDetectsInvalidStakeDistances(): void
    {
        $lane1   = new ShootingLane(Uuid::v4()->toRfc4122(), 'Lane 1', 50.0);
        $target1 = new Target(Uuid::v4()->toRfc4122(), TargetType::ANIMAL_GROUP_1, 'Deer', 'deer.png');

        $assignments = [
            new TournamentValidationAssignment(
                round: 1,
                lane: $lane1,
                target: $target1,
                stakes: ['red' => 10, 'blue' => 5, 'yellow' => 2],
                row: 1,
            ),
        ];

        $context = new TournamentValidationContext(
            ruleset: Ruleset::DSB_3D,
            expectedTargetCount: 1,
            assignments: $assignments,
        );

        $result = $this->validator->validate($context);

        self::assertFalse($result->isValid());
        $stakeIssues = $this->findIssuesByRule($result->issues(), 'Stake Distance');
        self::assertNotEmpty($stakeIssues, 'Should detect invalid stake distances for DSB_3D ruleset');
    }

    public function testDetectsMissingStakes(): void
    {
        $lane1   = new ShootingLane(Uuid::v4()->toRfc4122(), 'Lane 1', 50.0);
        $target1 = new Target(Uuid::v4()->toRfc4122(), TargetType::ANIMAL_GROUP_1, 'Deer', 'deer.png');

        $assignments = [
            new TournamentValidationAssignment(
                round: 1,
                lane: $lane1,
                target: $target1,
                stakes: ['red' => 40],
                row: 1,
            ),
        ];

        $context = new TournamentValidationContext(
            ruleset: Ruleset::DSB_3D,
            expectedTargetCount: 1,
            assignments: $assignments,
        );

        $result = $this->validator->validate($context);

        self::assertFalse($result->isValid());
        $stakeIssues = $this->findIssuesByRule($result->issues(), 'Stake Distance');
        self::assertNotEmpty($stakeIssues, 'Should detect missing stakes');
    }

    public function testDetectsTargetAssignedToMultipleLanes(): void
    {
        $lane1   = new ShootingLane(Uuid::v4()->toRfc4122(), 'Lane 1', 50.0);
        $lane2   = new ShootingLane(Uuid::v4()->toRfc4122(), 'Lane 2', 45.0);
        $target1 = new Target(Uuid::v4()->toRfc4122(), TargetType::ANIMAL_GROUP_1, 'Deer', 'deer.png');

        $assignments = [
            new TournamentValidationAssignment(
                round: 1,
                lane: $lane1,
                target: $target1,
                stakes: ['red' => 40, 'blue' => 25, 'yellow' => 15],
                row: 1,
            ),
            new TournamentValidationAssignment(
                round: 1,
                lane: $lane2,
                target: $target1,
                stakes: ['red' => 40, 'blue' => 25, 'yellow' => 15],
                row: 2,
            ),
        ];

        $context = new TournamentValidationContext(
            ruleset: Ruleset::FREEHAND,
            expectedTargetCount: 2,
            assignments: $assignments,
        );

        $result = $this->validator->validate($context);

        self::assertFalse($result->isValid());
        $uniquenessIssues = $this->findIssuesByRule($result->issues(), 'Target Uniqueness');
        self::assertNotEmpty($uniquenessIssues);
    }

    public function testDetectsLaneTargetInconsistencyAcrossRounds(): void
    {
        $lane1   = new ShootingLane(Uuid::v4()->toRfc4122(), 'Lane 1', 50.0);
        $target1 = new Target(Uuid::v4()->toRfc4122(), TargetType::ANIMAL_GROUP_1, 'Deer', 'deer.png');
        $target2 = new Target(Uuid::v4()->toRfc4122(), TargetType::ANIMAL_GROUP_2, 'Wolf', 'wolf.png');

        $assignments = [
            new TournamentValidationAssignment(
                round: 1,
                lane: $lane1,
                target: $target1,
                stakes: ['red' => 40, 'blue' => 25, 'yellow' => 15],
                row: 1,
            ),
            new TournamentValidationAssignment(
                round: 2,
                lane: $lane1,
                target: $target2,
                stakes: ['red' => 30, 'blue' => 20, 'yellow' => 12],
                row: 2,
            ),
        ];

        $context = new TournamentValidationContext(
            ruleset: Ruleset::FREEHAND,
            expectedTargetCount: 2,
            assignments: $assignments,
        );

        $result = $this->validator->validate($context);

        self::assertFalse($result->isValid());
        $consistencyIssues = $this->findIssuesByRule($result->issues(), 'Lane Target Consistency');
        self::assertNotEmpty($consistencyIssues);
    }

    public function testDetectsInvalidRoundNumber(): void
    {
        $lane1   = new ShootingLane(Uuid::v4()->toRfc4122(), 'Lane 1', 50.0);
        $target1 = new Target(Uuid::v4()->toRfc4122(), TargetType::ANIMAL_GROUP_1, 'Deer', 'deer.png');

        $assignments = [
            new TournamentValidationAssignment(
                round: 0,
                lane: $lane1,
                target: $target1,
                stakes: ['red' => 40, 'blue' => 25, 'yellow' => 15],
                row: 1,
            ),
        ];

        $context = new TournamentValidationContext(
            ruleset: Ruleset::FREEHAND,
            expectedTargetCount: 1,
            assignments: $assignments,
        );

        $result = $this->validator->validate($context);

        self::assertFalse($result->isValid());
        $roundIssues = $this->findIssuesByRule($result->issues(), 'Round Number');
        self::assertNotEmpty($roundIssues);
    }

    public function testDetectsNullLane(): void
    {
        $target1 = new Target(Uuid::v4()->toRfc4122(), TargetType::ANIMAL_GROUP_1, 'Deer', 'deer.png');

        $assignments = [
            new TournamentValidationAssignment(
                round: 1,
                lane: null,
                target: $target1,
                stakes: ['red' => 40, 'blue' => 25, 'yellow' => 15],
                row: 1,
            ),
        ];

        $context = new TournamentValidationContext(
            ruleset: Ruleset::FREEHAND,
            expectedTargetCount: 1,
            assignments: $assignments,
        );

        $result = $this->validator->validate($context);

        self::assertFalse($result->isValid());
        $laneIssues = $this->findIssuesByRule($result->issues(), 'Shooting Lane');
        self::assertNotEmpty($laneIssues);
    }

    public function testDetectsNullTarget(): void
    {
        $lane1 = new ShootingLane(Uuid::v4()->toRfc4122(), 'Lane 1', 50.0);

        $assignments = [
            new TournamentValidationAssignment(
                round: 1,
                lane: $lane1,
                target: null,
                stakes: ['red' => 40, 'blue' => 25, 'yellow' => 15],
                row: 1,
            ),
        ];

        $context = new TournamentValidationContext(
            ruleset: Ruleset::FREEHAND,
            expectedTargetCount: 1,
            assignments: $assignments,
        );

        $result = $this->validator->validate($context);

        self::assertFalse($result->isValid());
        $targetIssues = $this->findIssuesByRule($result->issues(), 'Target');
        self::assertNotEmpty($targetIssues);
    }

    public function testDetectsUnbalancedTargetGroups(): void
    {
        $lane1 = new ShootingLane(Uuid::v4()->toRfc4122(), 'Lane 1', 50.0);
        $lane2 = new ShootingLane(Uuid::v4()->toRfc4122(), 'Lane 2', 45.0);
        $lane3 = new ShootingLane(Uuid::v4()->toRfc4122(), 'Lane 3', 35.0);

        $target1 = new Target(Uuid::v4()->toRfc4122(), TargetType::ANIMAL_GROUP_1, 'Deer', 'deer.png');
        $target2 = new Target(Uuid::v4()->toRfc4122(), TargetType::ANIMAL_GROUP_1, 'Elk', 'elk.png');
        $target3 = new Target(Uuid::v4()->toRfc4122(), TargetType::ANIMAL_GROUP_1, 'Bear', 'bear.png');

        $assignments = [
            new TournamentValidationAssignment(
                round: 1,
                lane: $lane1,
                target: $target1,
                stakes: ['red' => 40, 'blue' => 25, 'yellow' => 15],
            ),
            new TournamentValidationAssignment(
                round: 1,
                lane: $lane2,
                target: $target2,
                stakes: ['red' => 40, 'blue' => 25, 'yellow' => 15],
            ),
            new TournamentValidationAssignment(
                round: 1,
                lane: $lane3,
                target: $target3,
                stakes: ['red' => 40, 'blue' => 25, 'yellow' => 15],
            ),
        ];

        $context = new TournamentValidationContext(
            ruleset: Ruleset::DSB_3D,
            expectedTargetCount: 3,
            assignments: $assignments,
        );

        $result = $this->validator->validate($context);

        self::assertFalse($result->isValid());
        $balanceIssues = $this->findIssuesByRule($result->issues(), 'Target Group Balance');
        self::assertNotEmpty($balanceIssues, 'Should detect unbalanced target groups for DSB_3D');
    }

    public function testSkipsTargetGroupBalancingForFreehand(): void
    {
        $lane1 = new ShootingLane(Uuid::v4()->toRfc4122(), 'Lane 1', 50.0);
        $lane2 = new ShootingLane(Uuid::v4()->toRfc4122(), 'Lane 2', 45.0);

        $target1 = new Target(Uuid::v4()->toRfc4122(), TargetType::ANIMAL_GROUP_1, 'Deer', 'deer.png');
        $target2 = new Target(Uuid::v4()->toRfc4122(), TargetType::ANIMAL_GROUP_1, 'Elk', 'elk.png');

        $assignments = [
            new TournamentValidationAssignment(
                round: 1,
                lane: $lane1,
                target: $target1,
                stakes: ['red' => 40, 'blue' => 25, 'yellow' => 15],
            ),
            new TournamentValidationAssignment(
                round: 1,
                lane: $lane2,
                target: $target2,
                stakes: ['red' => 40, 'blue' => 25, 'yellow' => 15],
            ),
        ];

        $context = new TournamentValidationContext(
            ruleset: Ruleset::FREEHAND,
            expectedTargetCount: 2,
            assignments: $assignments,
        );

        $result = $this->validator->validate($context);

        $balanceIssues = $this->findIssuesByRule($result->issues(), 'Target Group Balance');
        self::assertEmpty($balanceIssues, 'Freehand ruleset should not enforce target group balancing');
    }

    public function testDetectsStakeExceedingLaneMaxDistance(): void
    {
        $lane1   = new ShootingLane(Uuid::v4()->toRfc4122(), 'Lane 1', 25.0);
        $target1 = new Target(Uuid::v4()->toRfc4122(), TargetType::ANIMAL_GROUP_1, 'Deer', 'deer.png');

        $assignments = [
            new TournamentValidationAssignment(
                round: 1,
                lane: $lane1,
                target: $target1,
                stakes: ['red' => 40, 'blue' => 25, 'yellow' => 15],
                row: 1,
            ),
        ];

        $context = new TournamentValidationContext(
            ruleset: Ruleset::DSB_3D,
            expectedTargetCount: 1,
            assignments: $assignments,
        );

        $result = $this->validator->validate($context);

        self::assertFalse($result->isValid());
        $stakeIssues = $this->findIssuesByRule($result->issues(), 'Stake Distance');
        self::assertNotEmpty($stakeIssues, 'Should detect stake distance exceeding lane max');
    }

    /**
     * @param list<TournamentValidationIssue> $issues
     *
     * @return list<TournamentValidationIssue>
     */
    private function findIssuesByRule(array $issues, string $rule): array
    {
        return array_values(array_filter(
            $issues,
            static fn ($issue): bool => $issue->rule === $rule,
        ));
    }

    /** @param list<TournamentValidationIssue> $issues */
    private function formatIssues(array $issues): string
    {
        if ($issues === []) {
            return '(no issues)';
        }

        $messages = [];
        foreach ($issues as $issue) {
            $messages[] = '[' . $issue->rule . '] ' . $issue->message;
        }

        return implode('; ', $messages);
    }
}
