# AI Agent Instructions

This document provides guidance for AI agents working on the archery-tournament codebase.

## Project Architecture

The project follows **Clean Architecture** with distinct layers:

```
src/
├── Domain/           # Entities, Value Objects, Repository Interfaces
├── Application/      # Commands, Handlers, Services, Queries
├── Infrastructure/   # Repository Implementations, Storage, Security
└── Presentation/     # Controllers, Inputs, Twig Extensions, Views
```

### Key Patterns

1. **Command Pattern**: Commands are simple readonly DTOs in `Application/Command/`. Each command has a corresponding `Handler` class that returns `CommandResult`.

2. **Input Validation**: Presentation layer uses `Input` classes that:
   - Parse data from `Request` objects via static `fromRequest()` method
   - Validate via `errors()` method returning `list<string>`
   - Convert to commands via `toCommand()` method

3. **Repository Pattern**: Interfaces live in `Domain/Repository/`, implementations in `Infrastructure/Persistence/Dbal/`.

## Quality Pipeline

Run the full quality pipeline before committing:

```bash
make qa
```

Individual checks:
- `make qa-cs` - Coding standards (uses `-n` flag to suppress warnings)
- `make qa-static` - PHPStan static analysis
- `make qa-tests` - PHPUnit tests with testdox output

## Testing Conventions

### Test Support Classes

Located in `tests/Unit/Support/`:
- `InMemoryArcheryGroundRepository` - In-memory repository for unit tests
- `SpyTargetImageStorage` - Spy implementation for image storage

**Important**: When adding methods to repository interfaces, you MUST update `InMemoryArcheryGroundRepository` or PHPStan will fail.

### Test Structure

Tests use PHPUnit 13+ with attributes:

```php
#[CoversClass(MyHandler::class)]
final class MyHandlerTest extends TestCase
{
    #[Test]
    public function myTestCase(): void
    {
        // Arrange, Act, Assert
    }
}
```

### Required Test Coverage

When creating new command handlers, always create tests covering:
1. Success case (happy path)
2. Failure when parent entity not found
3. Failure when target entity not found
4. Edge cases specific to the feature

## Frontend (Twig Templates)

### Modal Dialogs

Modals use native `<dialog>` elements with this structure:

```html
<dialog id="my-dialog" class="modal">
    <form method="post" class="modal__body">
        <input type="hidden" name="_token" id="my-token">
        <h3>Dialog Title</h3>
        <!-- fields -->
        <div class="modal__actions">
            <button class="button ghost" type="button" data-my-close>Cancel</button>
            <button class="button" type="submit">Submit</button>
        </div>
    </form>
</dialog>
```

### Icon Select Component

Custom select with icons uses `[data-icon-select]` attribute. Options need:
- `data-value` - The option value
- `data-icon` - Icon identifier (optional for visual updates)
- `data-label` - Display label (optional for visual updates)

### JavaScript Pattern

JavaScript is inline in templates within IIFE:

```javascript
<script>
(() => {
    // Get references
    const dialog = document.getElementById('my-dialog');
    
    // Define functions
    const openDialog = (button) => { ... };
    const closeDialog = () => { ... };
    
    // Event listeners
    document.addEventListener('click', (event) => { ... });
})();
</script>
```

## Common Mistakes to Avoid

### 1. Forgetting Test Support Updates

When adding repository interface methods:
```
✗ Add method to interface only
✓ Add method to interface AND InMemoryArcheryGroundRepository
```

### 2. Not Writing Tests for New Code

Every new command handler needs a test file. Do not skip this step.

### 3. Leaving Obsolete Code Behind

When replacing functionality:
- Remove the old command, handler, input, controller, and tests
- Remove obsolete repository methods
- Update any templates referencing old routes

### 4. Running PHPCS with Warnings

The project ignores warnings. Use:
```bash
vendor/bin/phpcs -n    # Correct
vendor/bin/phpcs       # Wrong (shows warnings)
```

### 5. Incomplete Modal Refactoring

When changing modal functionality:
1. Update the HTML structure
2. Update JavaScript references (element IDs, data attributes)
3. Update the button that triggers the modal (data-* attributes, class name)
4. Update event listeners for the new class/selector

### 6. Forgetting Rules Overview Updates

When modifying validation rules, rulesets, or generator options:
- Update `RulesController` if adding new data sources
- Update `templates/tournament/rules.html.twig` to display new information
- Update `TargetType` enum when changing target group definitions
- Update `Ruleset` enum when changing stake distance ranges or balancing rules
- Run the integration test in `tests/Integration/Controller/RulesControllerTest.php`

The Rules Overview page (`/tournaments/rules`) is the user-facing documentation generated from code. It must stay synchronized with the domain.

## CSRF Token Convention

CSRF tokens follow the pattern:
```
csrf_token('action_name_' ~ entityId)
csrf_token('action_name_' ~ parentId ~ '_' ~ childId)
```

Controller validation:
```php
$this->isCsrfTokenValid('action_name_' . $id . '_' . $childId, $token)
```

## File Naming Conventions

| Type | Location | Naming |
|------|----------|--------|
| Command | `Application/Command/{Entity}/` | `{Action}.php` |
| Handler | `Application/Command/{Entity}/` | `{Action}Handler.php` |
| Input | `Presentation/Input/{Entity}/` | `{Action}Input.php` |
| Controller | `Presentation/Controller/{Entity}/` | `{Action}Controller.php` |
| Test | `tests/Unit/Command/{Entity}/` | `{Action}HandlerTest.php` |

## Database

Migrations are in `migrations/`. Run with:
```bash
make migrate
```

The project uses Doctrine DBAL directly (no ORM). Repository implementations write raw SQL.
