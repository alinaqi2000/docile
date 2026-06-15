# 04 — Coding Standards & Engineering Principles

These rules are **enforced by CI** (PHPStan, PHP-CS-Fixer, PHPUnit coverage). PRs that break them
do not merge. This file is the canonical "base MD" of how Docile code is written.

## Language & types

1. **Every PHP file starts with** `declare(strict_types=1);` (after the opening `<?php`).
2. **Type everything**: parameters, return types, properties. Use `void`/`never` where apt.
   No untyped `mixed` unless genuinely unavoidable (and documented why).
3. **`final` by default.** Open for extension only via interfaces and explicit extension points.
   Prefer composition over inheritance.
4. **Immutability by default.** Value objects are `final readonly class`. Mutation returns a new
   instance (`with*()` methods).
5. **Constructor property promotion** for dependencies; `readonly` for injected collaborators.
6. **Enums** instead of class constants for closed sets. **Typed class constants** where useful.
7. **No nullable soup**: prefer Null Object / `Optional`-style returns or explicit exceptions over
   sprinkling `?T` and `?->` everywhere.
8. Target **PHP 8.3** features (readonly classes, `#[\Override]`, DNF types, json_validate).

## Forbidden in library (framework) code

- ❌ No global mutable state, no service locators-as-singletons, no facades.
- ❌ No `static` properties holding request/runtime state (breaks worker mode).
- ❌ No `exit` / `die` / `header()` / `echo` / `print` / `var_dump` / `dd()` in `src/`.
- ❌ No `@` error suppression. No `error_reporting(0)`/`(1)`.
- ❌ No `extract()`, no variable-variables, no `eval`.
- ❌ No direct superglobal access in domain/application/most infra. HTTP input comes from PSR-7.
- ❌ No hand-rolled `.env` parsing — use `vlucas/phpdotenv`.

## Architecture rules (the dependency rule)

- Domain depends on **nothing** framework-specific.
- Application depends on Domain + abstractions (ports), never on Infrastructure.
- Infrastructure & Presentation may depend inward only.
- Packages must keep the dependency graph in `03-MONOREPO.md` acyclic. New cross-package deps
  require updating that table.

## Naming

- Interfaces: `FooInterface` (matching PSR ecosystem convention used across Docile).
- Abstract base: `AbstractFoo` only when it's a true template; otherwise prefer composition.
- Exceptions: `FooException`, grouped under `<Package>\Exception\`.
- Attributes: imperative/declarative nouns — `#[Route]`, `#[AsCommand]`, `#[AsListener]`,
  `#[Authorize]`, `#[Inject]`.
- Value objects: domain nouns — `EmailAddress`, `Money`, `TenantId`.

## Errors & exceptions

- Throw typed exceptions; never return error codes/`false` for exceptional paths.
- Library code throws; only the HTTP/console boundary converts exceptions to responses/exit codes.
- Each package defines an exception marker interface (e.g. `Docile\Http\Exception\HttpExceptionInterface`).

## Documentation

- Public APIs get docblocks **only where types can't express intent** (e.g. array shapes via
  `@param array{...}`, `@template` generics for collections). No redundant `@param int $id`.
- **Do not add narrative comments** that restate code. Comments explain *why*, not *what*.
- Every package has a `README.md` with install + a 10-line usage example.

## Testing policy (TDD)

1. **Red → Green → Refactor.** New behavior starts with a failing test.
2. **100% line coverage** of framework `src/` is the target; coverage gate in CI (start at a high
   threshold and ratchet to 100%). Coverage is necessary, not sufficient.
3. **Mutation testing** (Infection) on critical packages (container, routing, security, bus) to
   ensure tests assert behavior, not just execute lines.
4. Test types:
   - **Unit** — pure, no I/O, the majority.
   - **Integration** — real collaborators (e.g. container wiring, Doctrine against SQLite).
   - **Feature** — full HTTP/console through the kernel using `docile/testing`.
5. Tests live in each package's `tests/`, namespace `Docile\<Pkg>\Tests\`.
6. No sleeping on real time, no real network. Use fakes: `FakeClock`, `ArrayCache`,
   `InMemoryTransport`, `FakeBroadcaster`.
7. Tests must be deterministic and parallel-safe.

## Static analysis & style

- **PHPStan level `max`** (9) + `phpstan-strict-rules` + `phpstan-deprecation-rules`. Bleeding-edge
  on. Baseline only as a temporary, shrinking measure.
- **PHP-CS-Fixer** with PER-CS 2.0 / PSR-12 base + curated rule set (ordered imports, native
  function invocation, strict comparison `===`, trailing commas, single-quote, declare_strict).
- No `// phpcs:ignore` / `@phpstan-ignore` without an inline reason.

## Performance & security defaults

- Hash passwords with **argon2id**. Constant-time comparisons for secrets (`hash_equals`).
- Escape all output in templates; APIs emit JSON via the serializer (no manual `json_encode` of
  domain objects).
- Security headers, CSRF, and rate limiting on by default in the skeleton.
- Avoid reflection on hot paths in production — use the **compiled container** and route cache.

## Definition of Done (per change)

- [ ] Tests written first, now green; coverage not decreased.
- [ ] PHPStan max passes; PHP-CS-Fixer clean.
- [ ] Public API documented (README/docblocks where needed).
- [ ] No forbidden constructs; worker-safe (no new global state).
- [ ] Changelog entry if user-facing.
