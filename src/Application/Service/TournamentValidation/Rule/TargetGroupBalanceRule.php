<?php

declare(strict_types=1);

namespace App\Application\Service\TournamentValidation\Rule;

use App\Application\Service\TournamentValidation\TournamentValidationContext;
use App\Application\Service\TournamentValidation\TournamentValidationIssue;
use App\Domain\ValueObject\TargetType;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

use function array_fill_keys;
use function array_filter;
use function array_map;
use function count;
use function intdiv;

#[AsTaggedItem(priority: 120)]
final class TargetGroupBalanceRule implements TournamentValidationRule
{
    /** @return list<TournamentValidationIssue> */
    public function validate(TournamentValidationContext $context): array
    {
        $issues  = [];
        $ruleset = $context->ruleset;

        if (! $ruleset->supportsTargetGroupBalancing()) {
            return [];
        }

        $requiredTypes = $ruleset->requiredTargetTypes();
        $groupCount    = count($requiredTypes);

        if ($groupCount === 0) {
            return [
                new TournamentValidationIssue(
                    rule: 'Target Group Balance',
                    message: 'No target groups are configured for this ruleset.',
                ),
            ];
        }

        $expectedTotal = $context->expectedTargetCount;
        if ($expectedTotal % $groupCount !== 0) {
            $issues[] = new TournamentValidationIssue(
                rule: 'Target Group Balance',
                message: 'Number of targets (' . $expectedTotal . ') must be divisible by the number of target groups (' . $groupCount . ').',
                context: ['expected' => $expectedTotal, 'groups' => $groupCount],
            );

            return $issues;
        }

        $actualTotal = count(array_filter(
            $context->assignments,
            static fn ($assignment): bool => $assignment->round > 0
                && $assignment->lane !== null
                && $assignment->target !== null,
        ));

        if ($actualTotal !== $expectedTotal) {
            return $issues;
        }

        $expectedPerGroup = intdiv($expectedTotal, $groupCount);
        $counts           = array_fill_keys(
            array_map(static fn (TargetType $type): string => $type->value, $requiredTypes),
            0,
        );

        foreach ($context->assignments as $assignment) {
            if ($assignment->target === null) {
                continue;
            }

            $type = $assignment->target->type();
            if (! isset($counts[$type->value])) {
                continue;
            }

            $counts[$type->value]++;
        }

        foreach ($requiredTypes as $type) {
            $actual = $counts[$type->value] ?? 0;
            if ($actual === $expectedPerGroup) {
                continue;
            }

            $issues[] = new TournamentValidationIssue(
                rule: 'Target Group Balance',
                message: 'Target group "' . $type->name . '" must be assigned ' . $expectedPerGroup . ' times but is assigned ' . $actual . '.',
                context: ['group' => $type->value, 'expected' => $expectedPerGroup, 'actual' => $actual],
            );
        }

        return $issues;
    }
}
