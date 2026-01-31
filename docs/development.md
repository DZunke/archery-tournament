# Development Guide

This guide covers local development and quality checks.

## Requirements

- PHP 8.4
- SQLite PDO extension (`pdo_sqlite`) enabled
- File uploads write to `public/uploads/targets`
- Composer

## Install Dependencies

```bash
composer install
```

## Initialize the Database

The project uses Doctrine DBAL with a simple SQLite database.

```bash
php bin/console app:db:init
```

To reset the database back to an empty state:

```bash
php bin/console app:db:reset --force
```

To seed sample data (uses test fixtures, requires dev dependencies):

```bash
php bin/console app:db:seed --reset
```

## Docker (FrankenPHP)

For a consistent HTTP environment, use the provided Docker Compose setup:

```bash
docker compose up --build
```

Then open `http://localhost:8080` and run the DB init inside the container if needed:

```bash
docker compose exec app php bin/console app:db:init
```

## Static Analysis

```bash
php vendor/bin/phpstan analyse -c phpstan.neon --memory-limit=-1
```

## UI Smoke Checks (Playwright)

We validate key UI flows with Playwright against the Docker/FrankenPHP container:

1. `docker compose up --build`
2. `docker compose exec app php bin/console app:db:seed --reset`
3. Open `http://localhost:8080` and verify:
   - Archery ground listing loads
   - Manage page loads, filters work
   - Add/remove target with image works

## Common Tasks

- Run generator: `php bin/console app:generate-tournament <archery-ground-id>`
- Toggle stake randomization: `-r` or `--randomize-stakes-between-rounds`
- Run UI server: `php -S 127.0.0.1:8000 -t public`
- Reset database: `php bin/console app:db:reset --force`
- Seed sample data: `php bin/console app:db:seed --reset`

## Fixtures

Fixtures live in `tests/Fixtures` for manual seeding or testing. The CLI now reads from the database, so create an archery ground in the UI before running the generator.
