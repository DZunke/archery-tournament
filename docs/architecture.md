# Architecture Overview

This project is organized around a clean separation of domain logic, application orchestration, and presentation (CLI). The core generator is intentionally step-based to support growth and configurability.

## Layers

- **Domain** (`src/Domain`)
  - Entities: `Tournament`, `TournamentTarget`, `ArcheryGround`, `Target`
  - Value objects: `Ruleset`, `TargetType`, `StakeDistances`
  - Domain rules are expressed through value objects and entities.

- **Application** (`src/Application`)
  - Generation pipeline and steps
  - Request and result DTOs
  - Commands/queries and their handlers (CQRS-style)
  - CommandBus / QueryBus used by CLI + HTTP controllers
  - Orchestrates domain logic without depending on presentation

- **Presentation** (`src/Presentation`)
  - Symfony Console commands
  - Web UI controllers and Twig templates
  - Maps user input into generation requests

## Current Boundaries

- Persistence uses Doctrine DBAL (no ORM) with custom hydrators.
- Controllers validate input via DTOs in `src/Presentation/Input` before dispatching commands.
- Fixtures remain available for testing and manual seeding.
- The pipeline produces a `Tournament` with assigned `TournamentTarget`s.
- The CLI prints the tournament output.

## Planned Growth

- Persistence layer (database + repositories)
- User interface (web or desktop)
- Export formats (PDF, CSV)

When adding new subsystems, keep these boundaries: domain rules in Domain, orchestration in Application, I/O in Presentation.
