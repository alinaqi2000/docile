# 07 — Development Environment

The maintainer's local PHP is **8.1.25 (EOL)**; Docile targets **PHP 8.3+**. We therefore run all
PHP/Composer/test commands **inside Docker** so the local PHP version is irrelevant. Docker is
available locally (`docker` + `docker compose`).

## Toolchain images (pulled and verified)

- `php:8.3-cli` — the **floor** runtime we test against (CI also runs 8.4).
- `composer:2` — bundles Composer 2.10 + PHP 8.5; used for dependency resolution and as a
  convenient general runner.

> Because the maintainer machine can't run PHP 8.3 natively, **always execute via Docker**. Never
> rely on the host `php`/`composer` for building or testing this project.

## Composer resolves for 8.3 regardless of runner

Root `composer.json` pins the platform so dependencies resolve for the floor:
```json
{ "config": { "platform": { "php": "8.3.999" } } }
```

## Everyday commands

Run from repo root. (A `Makefile` / `bin/dev` wrapper will encapsulate these.)

```bash
# Install dependencies (uses composer image; resolves for PHP 8.3 via platform pin)
docker run --rm -v "$PWD":/app -w /app composer:2 \
  composer install --no-interaction --no-progress

# Run the whole test suite on the FLOOR version (8.3)
docker run --rm -v "$PWD":/app -w /app php:8.3-cli \
  vendor/bin/phpunit

# Static analysis (PHPStan max)
docker run --rm -v "$PWD":/app -w /app php:8.3-cli \
  vendor/bin/phpstan analyse --no-progress

# Code style (check / fix)
docker run --rm -v "$PWD":/app -w /app php:8.3-cli \
  vendor/bin/php-cs-fixer fix --dry-run --diff
docker run --rm -v "$PWD":/app -w /app php:8.3-cli \
  vendor/bin/php-cs-fixer fix

# Coverage (needs a coverage driver; the dev Dockerfile adds pcov)
docker run --rm -v "$PWD":/app -w /app docile/dev \
  vendor/bin/phpunit --coverage-text --coverage-clover=coverage.xml
```

### Coverage note
`php:8.3-cli` ships no coverage driver. The project **dev Dockerfile** (`Dockerfile`, target `dev`)
installs **pcov** (fast, line coverage) + common extensions (intl, pdo_mysql, pdo_pgsql, pdo_sqlite,
redis, sodium, opcache). Build once:
```bash
docker build --target dev -t docile/dev .
```
Then use `docile/dev` for coverage and integration tests that need DB extensions.

## docker-compose (local app dev)

Services: `app` (FrankenPHP, worker mode + Mercure for realtime), `db` (PostgreSQL),
`redis` (queue/cache/broadcast backplane), `mailpit` (mail testing). Defined in
`docker-compose.yml`; the Caddyfile in `docker/` enables the built-in Mercure hub.

## Quick sanity check (no project deps needed)

```bash
docker run --rm -v "$PWD":/app -w /app php:8.3-cli php -r 'echo PHP_VERSION, PHP_EOL;'
```

## Conventions for agents working here
- Prefer `php:8.3-cli` for running already-installed `vendor/bin/*` tools (fast, floor version).
- Use `composer:2` for any `composer` subcommand.
- Use `docile/dev` (built from Dockerfile) when you need coverage or DB/redis extensions.
- Mount the repo at `/app`; keep `--no-interaction` in CI-like runs.
