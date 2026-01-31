# Development Guide

This guide covers local development and quality checks.

## Requirements

- PHP 8.4
- Composer

## Install Dependencies

```bash
composer install
```

## Static Analysis

```bash
php vendor/bin/phpstan analyse -c phpstan.neon --memory-limit=-1
```

## Common Tasks

- Run generator: `php bin/console app:generate-tournament`
- Toggle stake randomization: `-r` or `--randomize-stakes-between-rounds`

## Fixtures

Fixtures live in `tests/Fixtures`. `GetArcheryGroundQuery` currently returns the medium-sized fixture for CLI runs.
