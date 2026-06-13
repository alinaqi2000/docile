# 06 — Roadmap & Live Build Checklist

> Update the checkboxes as work lands. This is the resume point after any context loss.

## Build order (dependency-driven)

The order respects the dependency graph in `03-MONOREPO.md`: foundational packages first.

### Milestone 0 — Repo & tooling foundation
- [ ] Convert repo to monorepo skeleton (`packages/`, `skeleton/`, root tooling).
- [ ] Root `composer.json` (path repos), `phpunit.xml.dist`, `phpstan.neon.dist`,
      `.php-cs-fixer.dist.php`, `infection.json5.dist`.
- [ ] Dockerfile (PHP 8.3 + composer + pcov + ext) + docker-compose (db, redis, mercure).
- [ ] `.github/workflows/ci.yml` (test matrix 8.3/8.4 + phpstan + cs-fixer + coverage gate).
- [ ] Root `ARCHITECTURE.md` pointing at `docs/`. `CONTRIBUTING.md`, `LICENSE` (MIT).
- [ ] Preserve old framework as reference (move to `legacy/` or branch) — do NOT delete history.

### Milestone 1 — Core kernel (the ask: http + console + DI)
- [ ] `docile/support` (Str, Arr, Collection, Env, Pipeline, Clock) + 100% tests.
- [ ] `docile/contracts` (shared interfaces) — minimal, grow as needed.
- [ ] `docile/container` (PSR-11 autowiring, scopes, contextual, attributes) + tests + mutation.
- [ ] `docile/config` (typed config repository + loaders) + tests.
- [ ] `docile/http` (PSR-7/15 kernel, middleware pipeline, responses, SapiEmitter) + tests.
- [ ] `docile/routing` (attribute + fluent routes, matcher, URL gen, runner middleware) + tests.
- [ ] `docile/console` (Kernel, Command, Input/Output, styling, **ProcessPool**, generators) + tests.
- [ ] `docile/foundation` (Application, ServiceProvider, bootstrap, exception handler) + tests.
- [ ] `docile/framework` meta + `docile/skeleton` (runnable DDD app: `docile serve`, one route).
- [ ] End-to-end: `composer create-project`-style smoke — boot app, hit a route, run a command.

### Milestone 2 — Domain & messaging
- [ ] `docile/events` (PSR-14 dispatcher + `#[AsListener]`).
- [ ] `docile/bus` (Command/Query/Event buses, CQRS, middleware).
- [ ] `docile/validation` + `docile/serializer` (typed request DTOs, API resources).
- [ ] `docile/log` (PSR-3 + Monolog), `docile/cache` (PSR-6/16 + drivers).

### Milestone 3 — Persistence
- [ ] `docile/doctrine` (DBAL/ORM/ODM bootstrap, migrations, repo base, tenant resolver).
- [ ] Multi-tenant migrate command wired to ProcessPool (`tenants:migrate --parallel=N`).

### Milestone 4 — Security
- [ ] `docile/security` (authn drivers, RBAC/ABAC policies `#[Authorize]`, hashing, CSRF,
      rate limit, security headers).

### Milestone 5 — Realtime & async
- [ ] `docile/queue` (transports sync/redis/amqp/sqs/db) + `queue:work` worker.
- [ ] `docile/broadcasting` (Mercure/SSE default, Pusher, native WS) bound to events.
- [ ] Runtime adapters: FrankenPHP worker, RoadRunner, Swoole.

### Milestone 6 — DX & docs
- [ ] `docile/testing` (test kernel, HTTP client, fakes, factories) + Pest plugin.
- [ ] `docile/openapi` (OpenAPI 3.1 from attributes) + docs UI.
- [ ] Documentation site; generators polished; example apps.

### Milestone 7 — Release engineering
- [ ] monorepo-builder split config; per-package read-only repos under `docile-php`.
- [ ] Packagist publish + claim `docile` vendor; semantic-release/tagging; `0.1.0` alpha.

## Current status
- ✅ Studied legacy framework. ✅ Locked decisions (`01-DECISIONS.md`). ✅ Blueprint written.
- ▶️ Next: Milestone 0 (monorepo + tooling) then Milestone 1 (container first).

## Definition of "framework v1.0"
Runtime-agnostic secure API + realtime + queues + Doctrine + multi-tenant process pool, 100%
core coverage, OpenAPI docs, create-project skeleton, published `docile/*` packages.
