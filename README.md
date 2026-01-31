# Archery Tournament Generator

Generate archery tournaments from a ruleset, available lanes, and target inventory. The generator assigns targets to lanes and produces stake distances within the ruleset ranges. Output is currently provided via a Symfony Console command.

## Status

This project is early-stage. The tournament generation pipeline is the primary working component. Database persistence and a user interface are planned.

## Quick Start

Requirements:

- PHP 8.4
- SQLite PDO extension (`pdo_sqlite`)
- File uploads write to `public/uploads/targets`

Run the generator (requires an archery ground ID from the UI):

```bash
php bin/console app:db:init
php bin/console app:generate-tournament <archery-ground-id>
```

Run the UI:

```bash
php -S 127.0.0.1:8000 -t public
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
