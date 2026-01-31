# Tournament Generation

This document describes the tournament generation pipeline: its purpose, architecture, and how to extend it safely.

## Purpose

The generator creates a tournament plan from three inputs:

- A ruleset (stake distance ranges and target groups)
- An archery ground (lanes and target inventory)
- A requested target count

Archery ground data currently comes from the database via `ArcheryGroundRepository`.

The CLI command now requires an archery ground ID to select which ground is used for generation.

The output is a `Tournament` populated with `TournamentTarget` entries, each containing:

- round number
- shooting lane
- target
- stake distances (per stake color)

## Key Types

- `TournamentGenerationRequest` (DTO): external inputs to the pipeline
- `TournamentResult` (DTO): mutable state passed between steps
- `Tournament`: final domain entity
- `TournamentTarget`: per-round assignment

## Pipeline Architecture

The generator is step-based. Each step focuses on one responsibility and mutates `TournamentResult`.

```
+-----------------------------+
| TournamentGenerationRequest |
+-------------+---------------+
              |
              v
+-----------------------------+
| TournamentGenerationPipeline|
+-------------+---------------+
              |
              v
    +------------------+
    |  Step 1           | CollectQualifiedLanes
    +------------------+
              |
              v
    +------------------+
    |  Step 2           | CalculateRequiredRounds
    +------------------+
              |
              v
    +------------------+
    |  Step 3           | PlaceTargetTypes
    +------------------+
              |
              v
    +------------------+
    |  Step 4           | PlaceTargetToTournamentLanes
    +------------------+
              |
              v
    +------------------+
    |  Step 5           | GenerateTournamentTargets
    +------------------+
              |
              v
+-----------------------------+
| Tournament (final output)   |
+-----------------------------+
```

Step ordering is controlled with `#[AsTaggedItem(priority: X)]`.

## Current Step Responsibilities

1. **CollectQualifiedLanes**
   - Filters lanes that can support the minimum required stake distance.

2. **CalculateRequiredRounds**
   - Calculates rounds as `ceil(targets / qualifiedLanes)`.

3. **PlaceTargetTypes**
   - Assigns lanes to target groups based on ruleset constraints.

4. **PlaceTargetToTournamentLanes**
   - Assigns actual targets from inventory to each selected lane.

5. **GenerateTournamentTargets**
   - Creates `TournamentTarget` entries for each round.
   - Generates stake distances within ruleset ranges.
   - Optionally re-randomizes stakes between rounds.

## Extending the Pipeline

### Add a New Step

Example: add a step that enforces a custom constraint.

```php
#[AsTaggedItem(priority: 465)]
final class EnforceNoAdjacentSameType implements TournamentGenerationStep
{
    public function getName(): string
    {
        return 'Enforce no adjacent same target type';
    }

    public function supports(TournamentResult $tournamentResult): bool
    {
        return $tournamentResult->ruleset->value === 'DSB_3D';
    }

    public function process(TournamentResult $tournamentResult): void
    {
        // Validate or rearrange $tournamentResult->selectedLanesPerTargetGroup
        // Throw TournamentGenerationFailed on invalid state.
    }
}
```

Guidelines:

- Keep each step focused on one responsibility.
- Validate prerequisites at the start of `process()`.
- Throw `TournamentGenerationFailed` for invalid state.

### Add a New Generation Option

Pattern:

1. Add a property to `TournamentGenerationRequest`.
2. Pass it through `TournamentResult` if steps need it.
3. Update steps to read the option.
4. Expose it in the CLI command via `#[Option]`.

Example (already implemented):

- `randomizeStakesBetweenRounds` toggles per-round stake randomization.

## Adding a New Ruleset

- Update `Ruleset` with a new enum case.
- Define `allowedTargetTypes()` and `stakeDistanceRanges()`.
- Update any selection logic in the CLI or future UI.

## Testing the Generator

- Run: `php bin/console app:generate-tournament`
- Use `-r` to verify stake randomization between rounds.

As persistence is added, ensure new data sources feed `TournamentGenerationRequest` rather than bypassing the pipeline.

## Tournament Validation

Manual edits can be validated against the ruleset from the tournament detail view.
Validation rules are implemented as independent rule classes and composed by the
`TournamentValidator` service.

### Current Validation Rules

- **Target Count**: ensures assigned targets match the configured target count.
- **Stake Distances**: checks each stake distance fits the ruleset ranges and lane maximums.

### Adding a New Validation Rule

1) Implement `TournamentValidationRule`:

```php
#[AsTaggedItem(priority: 300)]
final class RoundNumberRule implements TournamentValidationRule
{
    public function validate(Tournament $tournament): array
    {
        $issues = [];

        foreach ($tournament->targets() as $assignment) {
            if ($assignment->round() <= 0) {
                $issues[] = new TournamentValidationIssue(
                    rule: 'Round Number',
                    message: 'Round must be greater than zero.',
                );
            }
        }

        return $issues;
    }
}
```

2) The rule is auto-tagged and picked up by `TournamentValidator` via `AutowireIterator`.

Keep rules small and focused so they can be composed and tested independently.
