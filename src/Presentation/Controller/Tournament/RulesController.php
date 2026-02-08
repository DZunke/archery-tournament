<?php

declare(strict_types=1);

namespace App\Presentation\Controller\Tournament;

use App\Application\Service\TournamentValidation\Rule\AssignmentReferenceRule;
use App\Application\Service\TournamentValidation\Rule\LaneTargetConsistencyRule;
use App\Application\Service\TournamentValidation\Rule\LaneUniquenessRule;
use App\Application\Service\TournamentValidation\Rule\StakeDistanceRule;
use App\Application\Service\TournamentValidation\Rule\TargetCountRule;
use App\Application\Service\TournamentValidation\Rule\TargetGroupBalanceRule;
use App\Application\Service\TournamentValidation\Rule\TargetUniquenessRule;
use App\Application\Service\TournamentValidation\Rule\TournamentValidationRule;
use App\Domain\ValueObject\Ruleset;
use App\Domain\ValueObject\TargetType;
use ReflectionClass;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

use function array_map;
use function iterator_to_array;
use function preg_replace;

final class RulesController extends AbstractController
{
    /** @param iterable<TournamentValidationRule> $validationRules */
    public function __construct(
        #[AutowireIterator(tag: TournamentValidationRule::class)] private readonly iterable $validationRules,
    ) {
    }

    #[Route('/tournaments/rules', name: 'tournament_rules', methods: ['GET'])]
    public function __invoke(): Response
    {
        $targetGroups = array_map(
            static fn (TargetType $type): array => [
                'type' => $type,
                'label' => $type->label(),
                'description' => $type->zoneSizeDescription(),
                'zoneSize' => $type->zoneSizeRange(),
            ],
            TargetType::cases(),
        );

        $rulesets = array_map(
            static fn (Ruleset $ruleset): array => [
                'value' => $ruleset->value,
                'name' => $ruleset->name(),
                'description' => self::getRulesetDescription($ruleset),
                'allowedTargetTypes' => $ruleset->allowedTargetTypes(),
                'supportsBalancing' => $ruleset->supportsTargetGroupBalancing(),
                'stakeDistances' => array_map(
                    static fn (TargetType $type): array => [
                        'type' => $type,
                        'label' => $type->label(),
                        'ranges' => $ruleset->stakeDistanceRanges($type),
                    ],
                    $ruleset->allowedTargetTypes(),
                ),
            ],
            Ruleset::cases(),
        );

        $validationRules = array_map(
            fn (TournamentValidationRule $rule): array => [
                'name' => $this->extractRuleName($rule),
                'description' => $this->extractRuleDescription($rule),
                'details' => $this->extractRuleDetails($rule),
                'applicability' => $this->extractRuleApplicability($rule),
            ],
            iterator_to_array($this->validationRules),
        );

        $generatorOptions = [
            [
                'name' => 'Include Training-Only',
                'description' => 'When enabled, lanes and targets marked as "Training Only" are included in the tournament generation.',
                'details' => 'By default, training-only elements are excluded from official tournaments. Enable this option for practice sessions or informal events where all available lanes and targets should be used.',
                'affectsValidation' => false,
            ],
            [
                'name' => 'Randomize Stakes Between Rounds',
                'description' => 'When enabled, stake distances are randomized for each round instead of using consistent distances.',
                'details' => 'Within the allowed ranges defined by the ruleset, each round will have different stake distances. This increases variety and challenge across rounds while remaining within valid limits.',
                'affectsValidation' => false,
            ],
        ];

        return $this->render('tournament/rules.html.twig', [
            'targetGroups' => $targetGroups,
            'rulesets' => $rulesets,
            'validationRules' => $validationRules,
            'generatorOptions' => $generatorOptions,
        ]);
    }

    private static function getRulesetDescription(Ruleset $ruleset): string
    {
        return match ($ruleset) {
            Ruleset::DSB_3D => 'Official German Archery Federation (DSB) ruleset for 3D archery tournaments. '
                . 'Enforces strict stake distance ranges per target group and requires balanced distribution of target groups.',
            Ruleset::FREEHAND => 'Flexible ruleset without distance restrictions. '
                . 'Suitable for casual events, training sessions, or custom tournament formats where official constraints are not required.',
        };
    }

    private function extractRuleName(TournamentValidationRule $rule): string
    {
        $reflection = new ReflectionClass($rule);
        $shortName  = $reflection->getShortName();
        $name       = preg_replace('/Rule$/', '', $shortName);

        if ($name === null) {
            return $shortName;
        }

        $readable = preg_replace('/([a-z])([A-Z])/', '$1 $2', $name);

        return $readable ?? $name;
    }

    private function extractRuleDescription(TournamentValidationRule $rule): string
    {
        return match ($rule::class) {
            AssignmentReferenceRule::class
                => 'Ensures all assignments have valid round numbers and reference existing shooting lanes and targets.',
            LaneTargetConsistencyRule::class
                => 'Validates that each shooting lane is assigned to the same target across all rounds.',
            LaneUniquenessRule::class
                => 'Ensures each shooting lane is used only once per round.',
            StakeDistanceRule::class
                => 'Validates stake distances are within the allowed range for each target type according to the ruleset.',
            TargetCountRule::class
                => 'Verifies the tournament has exactly the expected number of target assignments.',
            TargetGroupBalanceRule::class
                => 'Ensures an equal distribution of targets across all required target groups.',
            TargetUniquenessRule::class
                => 'Ensures each target is assigned to only one shooting lane.',
            default => 'Validates tournament configuration.',
        };
    }

    private function extractRuleDetails(TournamentValidationRule $rule): string
    {
        return match ($rule::class) {
            AssignmentReferenceRule::class
                => 'If a shooting lane or target was deleted after assignment, this rule will flag the invalid reference. '
                    . 'Round numbers must be positive integers starting from 1.',
            LaneTargetConsistencyRule::class
                => 'In multi-round tournaments, each lane should consistently host the same target. '
                    . 'This ensures archers can navigate the course predictably regardless of starting position.',
            LaneUniquenessRule::class
                => 'Within a single round, no lane can appear twice. '
                    . 'Different rounds may reuse lanes with different targets as part of course design.',
            StakeDistanceRule::class
                => 'Each stake (Red, Blue, Yellow) has minimum and maximum distances defined per target group. '
                    . 'The lane\'s maximum distance also limits stake placement. '
                    . 'For DSB 3D, distances are strictly enforced. For Freehand, distances are unrestricted.',
            TargetCountRule::class
                => 'The tournament must have exactly the configured number of assigned targets. '
                    . 'Missing assignments or excess assignments will both trigger validation errors.',
            TargetGroupBalanceRule::class
                => 'For rulesets that require balancing (like DSB 3D), targets must be equally distributed among groups. '
                    . 'A 12-target tournament needs 3 targets from each of the 4 groups.',
            TargetUniquenessRule::class
                => 'Each physical target can only be placed on one shooting lane. '
                    . 'The same target cannot appear at multiple positions in the same round or tournament.',
            default => '',
        };
    }

    /** @return list<string> */
    private function extractRuleApplicability(TournamentValidationRule $rule): array
    {
        return match ($rule::class) {
            TargetGroupBalanceRule::class => ['DSB_3D'],
            StakeDistanceRule::class => ['DSB_3D'],
            default => $this->getAllRulesetValues(),
        };
    }

    /** @return list<string> */
    private function getAllRulesetValues(): array
    {
        return array_map(
            static fn (Ruleset $r): string => $r->value,
            Ruleset::cases(),
        );
    }
}
