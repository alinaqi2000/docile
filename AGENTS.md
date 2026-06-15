# Docile Framework — Agent Memory & Development Guidelines

> This file is the canonical reference for any AI agent or developer continuing
> work on this project. Read it fully before touching any code.

---

## 1. Project identity

| Key | Value |
|-----|-------|
| Framework name | **Docile** |
| Author | **Ali Naqi Al-Musawi** |
| GitHub org | `docile-php` |
| Packagist vendor | `docile` |
| PHP floor | **8.3** |
| PHP ceiling | 8.4 (tested in CI) |
| License | MIT |
| Dev env | Docker (`php:8.3-cli`, `composer:2`) |
| Primary branch | `main` |

All code is written by Ali Naqi Al-Musawi. Do not add AI attribution, AI-generated
comments, or mention of any AI tooling anywhere in source files, docblocks, commit
messages, or documentation. Code must read as the work of a senior PHP engineer who
has strong opinions about clean architecture.

---

## 2. Repository layout

```
docile/                          ← private monorepo (docile-php/framework)
├── packages/
│   ├── bus/                     → docile-php/bus          (private)
│   ├── config/                  → docile-php/config        (private)
│   ├── console/                 → docile-php/console       (private)
│   ├── container/               → docile-php/container     (private)
│   ├── events/                  → docile-php/events        (private)
│   ├── foundation/              → docile-php/foundation    (private)
│   ├── http/                    → docile-php/http          (private)
│   ├── routing/                 → docile-php/routing       (private)
│   ├── support/                 → docile-php/support       (private)
│   └── validation/              → docile-php/validation    (private)
├── legacy/                      ← original pre-rebuild codebase, do not touch
├── docs/                        ← architecture & design docs
├── .github/workflows/ci.yml     ← GitHub Actions (tests 8.3/8.4, PHPStan, CS, coverage)
├── AGENTS.md                    ← THIS FILE
├── ARCHITECTURE.md              ← high-level package map
├── CONTRIBUTING.md
├── LICENSE
├── composer.json                ← root monorepo, path autoloading all packages
├── phpunit.xml.dist
└── phpstan.neon.dist
```

Public-facing repos:
- `docile-php/docile` — the skeleton / create-project template (public)
- `docile-php/framework` — the monorepo mirror (private)

---

## 3. M1 milestone — completed packages

All packages: PHP 8.3, `declare(strict_types=1)`, all classes `final` unless
explicitly justified, PHPStan level max, PER-CS 2.0.

| Package | Namespace | Tests | Assertions | Key exports |
|---------|-----------|-------|------------|-------------|
| `docile/container` | `Docile\Container\` | 48 | ~90 | `Container`, PSR-11, `#[Inject]`, `#[Singleton]`, contextual bindings, method injection |
| `docile/config` | `Docile\Config\` | 77 | ~200 | `Repository`, dot-notation, `PhpFileLoader`, `DirectoryLoader`, `ChainLoader` |
| `docile/support` | `Docile\Support\` | 183 | 248 | `Str`, `Arr`, `Collection<TK,TV>`, `Pipeline`, `Env`, `SystemClock`, `FrozenClock`, `Optional<T>` |
| `docile/events` | `Docile\Events\` | 24 | 27 | PSR-14 `EventDispatcher`, `ListenerProvider`, `#[AsListener]`, `StoppableEvent` |
| `docile/bus` | `Docile\Bus\` | 19 | 27 | `MessageBus`, `CommandBus`, `QueryBus`, `MapLocator`, `LockingMiddleware`, `LoggingMiddleware` |
| `docile/http` | `Docile\Http\` | 174 | ~580 | PSR-7/15 `Kernel`, 6 response factories, `SapiEmitter`, `SecurityHeadersMiddleware`, `MethodOverrideMiddleware` |
| `docile/routing` | `Docile\Routing\` | 94 | 165 | `Router`, `Matcher`, `UrlGenerator`, `#[Route]`, `#[Get]`/`#[Post]`/`#[Put]`/`#[Patch]`/`#[Delete]` |
| `docile/validation` | `Docile\Validation\` | 29 | 131 | `Validator`, `ViolationList`, `#[Required]`, `#[Email]`, `#[Min]`, `#[Max]`, `RuleInterface` |
| `docile/console` | `Docile\Console\` | 70 | 165 | `Kernel`, `Command`, `Input`, `Output`, `CommandRegistry`, `ProcessPool` (`proc_open`+`stream_select`) |
| `docile/foundation` | `Docile\Foundation\` | 27 | 71 | `Application`, `AbstractServiceProvider`, `LoadConfiguration`, `RegisterServiceProviders`, `ExceptionHandler` |
| **Total** | | **745** | **1,576** | |

---

## 4. Console entry point

The framework's CLI binary is invoked as:

```bash
php docile deliver        # start the HTTP server / entry point
php docile <command>      # run any registered console command
```

The binary file is `bin/docile` (executable, shebang `#!/usr/bin/env php`).
The name `deliver` is intentional and on-brand — do not change it.

---

## 5. Coding standards (non-negotiable)

### PHP style
- Every file: `<?php` + blank line + `declare(strict_types=1);` + blank line + `namespace`
- All classes `final` by default. Abstract only when there is an explicit, documented
  design reason (e.g. `AbstractServiceProvider`, `StoppableEvent`).
- No `public` properties — use `private readonly` + getters, or `readonly` constructor promotion.
- No `static` mutable state anywhere in `src/`. Tests may use static fixtures.
- Typed exceptions in each package's `Exception\` sub-namespace.
- Return types always explicit. No `mixed` return without a `/** @return mixed */`
  explaining the design reason.
- `#[\Override]` on all methods that override a parent.
- Interfaces live in the same package as their primary implementation.
  Cross-package contracts use PSR interfaces (PSR-3, PSR-7, PSR-11, PSR-14, PSR-15).

### Docblocks
- Public API methods get a single-line `/** Short description. */` at minimum.
- Complex generics documented with `@template`, `@param`, `@return` PHPDoc.
- No `@author`, `@package`, `@since`, `@version` tags anywhere — they are noise.
- Do not add inline comments explaining *what* code does (code is self-documenting).
  Only add comments explaining *why* a non-obvious decision was made.

### Naming
| Concept | Convention |
|---------|-----------|
| Classes | `PascalCase` |
| Interfaces | `PascalCase` + `Interface` suffix (e.g. `LoaderInterface`) |
| Attributes | `PascalCase` (e.g. `#[AsListener]`) |
| Methods | `camelCase` |
| Constants | `UPPER_SNAKE_CASE` |
| Private properties | `camelCase` (no `_` prefix) |
| Test classes | `{Subject}Test` |
| Test methods | `test{What}{Context}` (e.g. `testMakeReturnsJsonResponse`) |
| Fixture classes | Lives in `tests/Fixtures/`, no `Test` suffix |

### Forbidden constructs (anywhere in `src/`)
- `exit`, `die`, `echo`, `print` (except `SapiEmitter::emit()`)
- `header()`, `setcookie()` (except `SapiEmitter`)
- `$_GET`, `$_POST`, `$_SERVER`, `$_SESSION` — use PSR-7 or `Env`
- `extract()`, variable-variables (`$$var`), `eval()`
- `global` keyword
- `compact()` (use explicit arrays)

### Worker-mode safety
Every `src/` class must be safe for long-running worker processes (FrankenPHP,
RoadRunner, Swoole). No per-process global state, no static mutable caches in `src/`.

---

## 6. Testing standards

- Framework: **PHPUnit 11**
- All tests extend `PHPUnit\Framework\TestCase`
- Use `#[CoversClass(ClassName::class)]` attribute, not `@covers` docblock
- `setUp()` and `tearDown()` must be `protected` and call `parent::`
- Test method names describe behaviour: `testDispatchCallsListenerWithEvent`
- No `@dataProvider` with string method names — use the attribute form:
  `#[DataProvider('providerMethodName')]`
- Mocks: prefer real objects (test doubles in `tests/Fixtures/`) over `createMock()`.
  Use `createMock()` only for interfaces you can't instantiate.
- Tests must be deterministic and order-independent (`executionOrder="random"` is set).
- `failOnRisky="true"` — no tests that touch output buffers without cleaning up,
  no tests that change global state without restoring it.

---

## 7. PHPStan rules

Config: `phpstan.neon.dist` at project root, level `max`.

Key enforced rules (`phpstan/phpstan-strict-rules`):
- `checkMissingCallableSignature: true`
- `checkMissingIterableValueType: true`
- No untyped array params — always `array<K, V>` or `list<T>`
- Generic types on `Collection<TKey, TValue>`, `Optional<T>`, `make()`, `call()`

Test fixtures in `tests/Fixtures/` are excluded from PHPStan strictness via:
```neon
parameters:
    ignoreErrors:
        - path: packages/*/tests/Fixtures/*
```

---

## 8. Commit message format

```
<type>(<scope>): <short imperative summary, ≤72 chars>

<optional body — explain WHY, not WHAT. wrap at 72 chars>
```

Types: `feat`, `fix`, `refactor`, `test`, `chore`, `docs`, `perf`, `ci`

Scopes: package name (`container`, `http`, `routing`, …) or `monorepo`, `ci`, `skeleton`

Rules:
- Subject line is lowercase after the colon, no trailing period
- Present tense imperative: "add", "fix", "remove" — not "added", "fixes"
- Body only when the change needs context a reviewer can't infer from the diff
- No co-author lines, no "Generated with" lines, no AI attribution
- No emoji in commit messages

Examples:
```
feat(routing): add #[Where] constraint attribute for parameter patterns

fix(http): type-narrow parsed body _method field before string ops

test(support): add edge-case coverage for Collection::chunk with remainder

chore(monorepo): regenerate autoloader after adding foundation namespace

ci: add PHP 8.4 to test matrix
```

Rules that are NOT allowed in commit messages:
- No "M1", "M2", "milestone" — those are internal planning terms, never in git history
- No "AI", "generated", "co-authored by Devin" or any similar attribution
- No ticket/issue refs unless an actual GitHub issue number exists

---

## 9. M2 roadmap (next milestone)

Priority order:

### `docile/doctrine`
- Doctrine ORM 3 bridge
- `EntityManagerServiceProvider`
- Multi-tenant migration runner
- `DoctrineRepository<T>` base class
- Repository pattern (no active-record)

### `docile/security`
- Auth: session-based + token-based (JWT)
- Password hashing (`sodium_crypto_pwhash`)
- RBAC: `Gate`, `Policy`, `can()` helper
- CSRF middleware
- Rate limiter middleware (token-bucket, PSR-6 cache backend)
- Signed URL generator

### `docile/cache`
- PSR-6 + PSR-16 adapters
- Backends: APCu, Redis, Filesystem, Array (for tests)
- Taggable cache pool

### `docile/skeleton` (the `composer create-project` template)
- Interactive installer (`CreateProjectCommand`)
- Presets: `api`, `web`, `minimal`
- Wires `docile deliver` binary
- Includes `.env.example`, `public/index.php`, `config/`, `src/`

### `docile/testing`
- `ApplicationTestCase` base
- `HttpTestCase` with a fluent request builder
- `ConsoleTestCase`
- Database transaction rollback trait

---

## 10. Infrastructure decisions (do not revisit without a doc)

| Decision | Choice | Rationale |
|----------|--------|-----------|
| ORM | Doctrine 3 | Full DDD; no active record coupling |
| HTTP | PSR-7/15 (`nyholm/psr7`) | Runtime-agnostic |
| Container | Custom (PSR-11) | No Laravel/Symfony dep, full control |
| Events | Custom (PSR-14) | Same reason |
| Testing | PHPUnit 11 | Standard; PHPStan understands it |
| CS | PHP-CS-Fixer, PER-CS 2.0 | Enforceable in CI |
| Static analysis | PHPStan level max | Catches real bugs before runtime |
| CLI binary | `bin/docile` | `php docile deliver` to start server |
| PHP floor | 8.3 | `readonly` classes, `#[\Override]`, `json_validate` |
| Packagist | `docile/*` | Clean, available namespace |
| Worker safety | Required | All src/ classes safe in FrankenPHP/RoadRunner |

---

## 11. Environment setup (for a new machine)

```bash
git clone git@github.com:docile-php/framework.git docile
cd docile

# Install deps (needs PHP 8.3+ or Docker)
docker run --rm -v "$PWD":/app -w /app composer:2 install

# Run full test suite
docker run --rm -v "$PWD":/app -w /app php:8.3-cli vendor/bin/phpunit --no-coverage

# PHPStan
docker run --rm -v "$PWD":/app -w /app php:8.3-cli vendor/bin/phpstan analyse --no-progress

# CS check
docker run --rm -v "$PWD":/app -w /app php:8.3-cli vendor/bin/php-cs-fixer fix --dry-run --diff
```

All three must pass before any commit lands on `main`.
