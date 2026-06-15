# Contributing to Docile

Thank you for your interest in contributing! This document covers the development workflow,
coding standards, and how to get a PR merged.

---

## Development environment

You need Docker (for the test matrix) and PHP 8.3+ with Composer 2 locally.

```bash
git clone https://github.com/docile-php/docile
cd docile
composer install
```

Run the full suite:

```bash
vendor/bin/phpunit --no-coverage
```

Or inside Docker (floor-version check):

```bash
docker run --rm -v "$PWD":/app -w /app php:8.3-cli vendor/bin/phpunit --no-coverage
```

---

## Coding standards

Every contribution must pass all three gates:

```bash
# Tests
vendor/bin/phpunit --no-coverage

# Static analysis
vendor/bin/phpstan analyse --no-progress

# Code style
vendor/bin/php-cs-fixer fix --dry-run --diff
# Auto-fix:
vendor/bin/php-cs-fixer fix
```

Key rules (enforced — see `docs/04-CODING-STANDARDS.md` for full details):

- `declare(strict_types=1)` on every PHP file
- All classes `final` unless there is an explicit, documented reason not to be
- PHPStan level `max` — no untyped `mixed` without a comment explaining why
- No global mutable state, no facades, no `exit`/`die`/`echo` in `src/`
- Tests written first (TDD); 100% line coverage target
- Typed exceptions under each package's `Exception\` namespace

---

## Monorepo structure

Each package lives under `packages/<name>/` with its own `composer.json` and `src/` / `tests/`.
The root `composer.json` wires them all together via PSR-4 path autoloading — there is no
`composer install` needed per package.

When adding a new package:
1. Create `packages/<name>/composer.json`, `src/`, `tests/`
2. Add autoload entries to the **root** `composer.json`
3. Run `composer dump-autoload --optimize`
4. Add the test directory to `phpunit.xml.dist`
5. Add the src directory to the PHPStan `paths`

---

## Pull request checklist

Before opening a PR, confirm all of the following:

- [ ] `vendor/bin/phpunit --no-coverage` — all tests green, no risky
- [ ] `vendor/bin/phpstan analyse --no-progress` — 0 errors
- [ ] `vendor/bin/php-cs-fixer fix --dry-run --diff` — clean
- [ ] New behaviour has tests; coverage not decreased
- [ ] No forbidden constructs (see `docs/04-CODING-STANDARDS.md`)
- [ ] Public API has appropriate docblocks (array shapes, `@template`, etc.)
- [ ] `CHANGELOG.md` entry added if user-facing change

---

## Reporting bugs

Open a GitHub Issue with:
- PHP version, Composer version, package version
- Minimal reproduction case
- Expected vs actual behaviour
