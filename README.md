# Archery Tournament Generator

Generate archery tournaments from a ruleset, available lanes, and target inventory. The generator assigns targets to lanes and produces stake distances within the ruleset ranges. Archery grounds and targets can now be managed in the UI, while tournament generation is still provided via the Symfony Console command.

## Status

This project is early-stage. The tournament generation pipeline is stable, and a lightweight UI for archery ground management is in place. Tournament generation in the UI is planned next.

## Quick Start

Requirements:

- PHP 8.4
- SQLite PDO extension (`pdo_sqlite`)
- File uploads write to `public/uploads/targets`

Initialize the database:

```bash
php bin/console app:db:init
```

Optional: seed sample data:

```bash
php bin/console app:db:seed --reset
```

Run the UI (create an archery ground if you did not seed):

```bash
php -S 127.0.0.1:8000 -t public
```

Generate a tournament (requires an archery ground ID):

```bash
php bin/console app:generate-tournament <archery-ground-id>
```

Run via Docker (FrankenPHP):

```bash
docker compose up --build
```

Then open: `http://localhost:8080`

Optional flags:

- `-r`, `--randomize-stakes-between-rounds` - randomize stake distances between rounds while keeping targets fixed.

## Documentation

Project documentation lives in `docs/README.md`.

Highlights:

- `docs/tournament-generation.md`: purpose, architecture, and extension patterns for the generation pipeline.
- `docs/architecture.md`: high-level project structure and boundaries.
- `docs/development.md`: local development and quality checks.

## Development

Install dependencies (if `vendor/` is not present):

```bash
composer install
```

Static analysis:

```bash
php vendor/bin/phpstan analyse -c phpstan.neon --memory-limit=-1
```

## Roadmap

- Persistence layer for archery grounds, targets, and tournaments
- Simple user interface for tournament setup and export
- Additional rulesets and configuration options
