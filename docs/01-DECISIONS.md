# 01 — Decisions (ADR Log)

Locked decisions. When implementation questions arise, this file wins. Each entry: decision,
status, rationale, consequences.

---

## D-001 — Runtime model: runtime-agnostic core ✅ LOCKED
**Decision.** The HTTP core is built on PSR-7 (messages) and PSR-15 (handlers/middleware). The
kernel consumes a `ServerRequestInterface` and returns a `ResponseInterface`. Classic PHP-FPM /
`php -S` is the default runtime via a response *emitter*. First-class **adapters** exist for
**FrankenPHP (worker mode)**, **RoadRunner**, and **Swoole/OpenSwoole**.
**Rationale.** Scaling to millions and realtime should be a deployment choice, not a rewrite.
Keeps app code identical across runtimes. Avoids hard dependency on any PECL extension.
**Consequences.** Core must be free of request-scoped global state; per-request services are
resolved from a child/scoped container so worker mode is safe. No `exit`/`die` in library code.

## D-002 — Realtime: driver-based broadcasting ✅ LOCKED
**Decision.** A single `Broadcaster` abstraction with swappable drivers:
- **Mercure/SSE** (works great with FrankenPHP's built-in hub) — default for browsers.
- **Pusher protocol** (Pusher, Ably, Soketi) — managed/self-hosted WebSockets.
- **Native WebSocket** server (long-running, via the async runtime).
Broadcasting is **bound to the event bus**: domain/application events can be marked
`ShouldBroadcast` and are pushed to channels (public/private/presence) automatically.
**Rationale.** Different deployments want different transports; the app shouldn't care.
**Consequences.** Need a channel authorization layer tied to RBAC.

## D-003 — Persistence: Doctrine ✅ LOCKED
**Decision.** Doctrine **ORM + DBAL** for SQL (MySQL, PostgreSQL, SQLite, SQL Server, etc.) and
Doctrine **ODM** for MongoDB (NoSQL). The Domain layer depends only on **repository interfaces**;
Doctrine implementations live in Infrastructure.
**Rationale.** Data Mapper (not ActiveRecord) keeps the domain pure — essential for DDD. Doctrine
is the most mature mapper in PHP and supports many stores.
**Consequences.** We ship a `docile/doctrine` bridge: connection management, migrations, a
`docile orm:*` command set, multi-tenant connection resolver, and repository base classes.

## D-004 — Package naming & vendor ✅ LOCKED
**Decision.** Composer vendor **`docile`** (verified free on Packagist). PHP namespace `Docile\`.
GitHub org **`docile-php`** (bare `docile` org taken; irrelevant to package names). Backup org
name `getdocile`.
**Consequences.** Claim the `docile` vendor on Packagist after first publish. Repo/CI URLs use
`docile-php`; these are trivially editable and touch no code.

## D-005 — Testing: PHPUnit core + Pest plugin ✅ LOCKED
**Decision.** The framework is tested with **PHPUnit 11** (100% line coverage target,
mutation-tested with Infection where valuable). We also ship a **Pest plugin** (`docile/pest`) so
app developers may write tests in either style.
**Consequences.** Provide a `docile/testing` package: `TestCase`, application/HTTP test kernel,
in-memory transports, fakes (clock, mailer, broadcaster, queue), and model/object factories.

## D-006 — Minimum PHP version: 8.3 ✅ LOCKED
**Decision.** Require **PHP >= 8.3** (recommend 8.4). PHP 8.1/8.2 are excluded; 8.1 is EOL and 8.3
gives us readonly classes, `#[\Override]`, typed class constants, json validate, and DNF types.
**Rationale.** A new framework should target supported, modern PHP. Local dev PHP (8.1) is bypassed
by using Docker (see `07-DEV-ENV.md`).
**Consequences.** CI test matrix: 8.3 and 8.4 (add 8.5 once dependencies are green).

## D-007 — Repository strategy: monorepo, split to read-only packages ✅ LOCKED
**Decision.** Develop everything in one repo (`docile-php/docile`). Use a monorepo splitter
(`tomasvotruba/monorepo-builder` style) to publish each `packages/*` to its own read-only repo and
Packagist package. Provide `docile/framework` (meta, requires all) and `docile/skeleton`
(create-project app).
**Rationale.** Best of both worlds: atomic cross-package changes during development; à-la-carte +
"all at once" installs for users.
**Consequences.** Each package has its own `composer.json`; root uses path repositories for local
dev. Tags are synchronized across packages on release.

## D-008 — DI container: our own, compiled, PSR-11 ✅ LOCKED
**Decision.** Build Docile's own autowiring container implementing `Psr\Container\ContainerInterface`,
with reflection-based autowiring in dev and an optional **compiled** container for production
(generated PHP, no reflection at runtime). Attribute support: `#[Inject]`, `#[Singleton]`,
`#[Factory]`, contextual bindings.
**Rationale.** "Its own DI" is an explicit project requirement and a differentiator. Compilation
keeps it fast under worker runtimes.
**Consequences.** Container is the foundation package; build and fully test it first.

## D-009 — Console: our own minimal console ✅ LOCKED
**Decision.** Build a lightweight console (Input/Output/Command/Kernel, attribute `#[AsCommand]`,
ANSI styling, argv parsing) rather than depend on `symfony/console`. Headline feature: the
**parallel process pool** (`docile`'s answer to Go's process pools) for fleet operations.
**Rationale.** Keeps the core minimal/self-contained and on-brand; the process pool is a key
differentiator best built natively on `proc_open` + `stream_select` (portable, non-blocking).
**Consequences.** We must implement styling/parsing/help ourselves; acceptable and well-scoped.

## D-010 — Messaging: CQRS buses + transports ✅ LOCKED
**Decision.** Provide `CommandBus`, `QueryBus`, and `EventBus` (PSR-14 dispatcher under the event
bus). Handlers discovered via attributes (`#[CommandHandler]`, `#[AsListener]`). Async work uses a
`docile/queue` package with transports: **sync**, **Redis**, **AMQP/RabbitMQ**, **Amazon SQS**,
**Beanstalkd**, **database**. Workers are runtime-agnostic.
**Consequences.** Buses live in `docile/bus`; the event dispatcher in `docile/events`.

## D-011 — Validation & serialization ✅ LOCKED
**Decision.** Attribute-based validation on DTOs/request objects (`#[Required]`, `#[Email]`,
`#[Length(min,max)]`, custom rules). A typed serializer maps requests → DTOs and domain → JSON
responses (API resources). No array-soup.
**Consequences.** `docile/validation` + `docile/serializer` packages.

## D-012 — Security model ✅ LOCKED
**Decision.** `docile/security`: authentication (session, token/JWT, API key), authorization via
**RBAC + ABAC policies** with `#[Authorize]` attribute and voters, password hashing (argon2id),
signed URLs, CSRF, rate limiting, and secure headers middleware. Channel authorization for
broadcasting reuses the same policy layer.
**Consequences.** Security middleware ships enabled with safe defaults in the skeleton.

## D-013 — Configuration & env ✅ LOCKED
**Decision.** Typed configuration objects + `config/*.php` returning arrays, environment via
`vlucas/phpdotenv` (replace the hand-rolled regex parser). `env()` reads only at boot into typed
config; runtime code reads config, not `getenv()`. Secrets via env or a secrets driver.
**Consequences.** Remove `framework/Helpers/environment.php` custom parser and `error_reporting(1)`.

## D-014 — Error handling & observability ✅ LOCKED
**Decision.** Central exception handler maps exceptions → PSR-7 responses (RFC 7807
`application/problem+json` for APIs). PSR-3 logging (Monolog bridge). OpenTelemetry-ready hooks for
tracing/metrics. Debug renderer only in dev.
**Consequences.** No `core_view("server-error")`-style `die()` in library code.

## D-015 — API documentation ✅ LOCKED
**Decision.** Generate **OpenAPI 3.1** from route + DTO + validation attributes; serve interactive
docs. "Best documentation" is a product goal: framework docs site + auto API docs for user apps.
**Consequences.** `docile/openapi` package, later milestone.

---

### Open questions (to confirm with maintainer later, non-blocking)
- OQ-1: Default realtime driver in skeleton — Mercure/SSE (leaning yes).
- OQ-2: Ship a built-in queue dashboard UI? (later)
- OQ-3: First-party admin/scaffolding generator scope.
