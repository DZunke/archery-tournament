**DANGER** - This Project is primary vibe coded for experimenting with vibe coding tactics

# Archery Tournament Generator

Generate archery tournaments from a ruleset, available lanes, and target inventory. The generator assigns targets to lanes and produces stake distances within the ruleset ranges. Archery grounds, targets, and tournaments can be managed in the UI, while the CLI remains available for quick generation.

## Status

This project is early-stage. The tournament generation pipeline is stable, and a lightweight UI for archery ground + tournament management is in place. The UI supports auto-generated tournaments that can be manually edited.

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

Run the UI:

```bash
php -S 127.0.0.1:8000 -t public
```

Generate a tournament via CLI (requires an archery ground ID):

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
