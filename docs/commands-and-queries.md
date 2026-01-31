# Commands, Queries, and Buses

This document describes the CQRS-style application flow that powers the UI and CLI. It explains the conventions, where to put new code, and how to extend the system safely.

## Why This Structure

- **Handlers stay pure**: all input validation happens before commands/queries reach handlers.
- **Controllers stay thin**: they only map HTTP to DTOs and dispatch to a bus.
- **Easy to extend**: new features add a command/query + handler, without changing controllers.

## High-Level Flow

```
HTTP/CLI
  |
  v
Controller / Command
  |
  v
Input DTO (validation)
  |
  v
Command or Query
  |
  v
CommandBus / QueryBus
  |
  v
Handler
  |
  v
Repository / Storage / Domain
  |
  v
Result (CommandResult or data)
```

## Conventions

- **Commands** live in `src/Application/Command/...`
- **Queries** live in `src/Application/Query/...`
- **Handlers** are invokable classes suffixed with `Handler`
- **Buses** resolve handlers by convention: `App\...\Foo` → `App\...\FooHandler`
- **Input DTOs** live in `src/Presentation/Input/...` and own validation

Handlers are registered as public services so the bus can resolve them.

## Add a New Command

Example: add a command to archive a target.

1) Create the command:

```php
final readonly class ArchiveTarget
{
    public function __construct(
        public string $archeryGroundId,
        public string $targetId,
    ) {
    }
}
```

2) Create the handler:

```php
final readonly class ArchiveTargetHandler
{
    public function __construct(private ArcheryGroundRepository $repository)
    {
    }

    public function __invoke(ArchiveTarget $command): CommandResult
    {
        $this->repository->archiveTarget($command->archeryGroundId, $command->targetId);

        return CommandResult::success('Target archived.');
    }
}
```

3) Add input validation in a DTO:

```php
final readonly class ArchiveTargetInput
{
    public function __construct(public string $targetId)
    {
    }

    public static function fromRequest(Request $request): self
    {
        return new self((string) $request->request->get('target_id', ''));
    }

    /** @return list<string> */
    public function errors(): array
    {
        return $this->targetId === '' ? ['Target ID is required.'] : [];
    }

    public function toCommand(string $archeryGroundId): ArchiveTarget
    {
        return new ArchiveTarget($archeryGroundId, $this->targetId);
    }
}
```

4) Dispatch from the controller:

```php
$input = ArchiveTargetInput::fromRequest($request);
if ($errors = $input->errors()) {
    // flash errors
    // redirect
}

$result = $this->commandBus->dispatch($input->toCommand($id));
```

## Add a New Query

Example: list targets in a specific group.

1) Create the query:

```php
final readonly class ListTargetsByType
{
    public function __construct(
        public string $archeryGroundId,
        public TargetType $type,
    ) {
    }
}
```

2) Create the handler:

```php
final readonly class ListTargetsByTypeHandler
{
    public function __construct(private ArcheryGroundRepository $repository)
    {
    }

    /** @return list<Target> */
    public function __invoke(ListTargetsByType $query): array
    {
        $ground = $this->repository->find($query->archeryGroundId);
        if ($ground === null) {
            return [];
        }

        return $ground->targetStorageByType($query->type);
    }
}
```

3) Ask the query bus from a controller:

```php
$targets = $this->queryBus->ask(new ListTargetsByType($id, TargetType::ANIMAL_GROUP_1));
```

## Input DTOs (Validation Boundary)

All user input validation happens in DTOs under `src/Presentation/Input/...`.
This keeps the handlers focused on orchestration and domain rules.

If you add new validations, place them in DTOs, not in handlers.

## Storage / Persistence Boundaries

Use interfaces in the Domain or Application layer:

- `ArcheryGroundRepository` for DB operations
- `TargetImageStorage` for file storage

Implementations live in `src/Infrastructure/...` (DBAL, local filesystem).
This keeps handlers portable and testable.

## Testing Strategy

- Unit-test handlers with in-memory repositories and spy storage.
- Use Playwright for smoke tests in Docker/FrankenPHP.

Handler tests live under `tests/Unit/Command` and `tests/Unit/Infrastructure`.

