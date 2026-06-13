# Docile — Architecture Overview

> For full detail see `docs/02-ARCHITECTURE.md` and the individual package specs in `docs/05-PACKAGES.md`.

---

## Package dependency graph

```
docile/support          (no docile deps — pure utilities)
docile/container        (no docile deps — PSR-11)
docile/config           (no docile deps — PHP file loaders)
docile/events           (no docile deps — PSR-14)
docile/bus              (no docile deps — CQRS message bus)
docile/validation       (no docile deps — attribute validator)
docile/http             (PSR-7/15/17, nyholm/psr7)
docile/routing          (PSR-7/15 interfaces only)
docile/console          (PSR-11 container interface only)
docile/foundation       (container + config + http + console)
```

No package depends on `docile/foundation` — it is the composition root only.

---

## Layer map (DDD)

```
┌─────────────────────────────────────────────┐
│  Presentation  (http kernel, console kernel) │  ← docile/http, docile/console
├─────────────────────────────────────────────┤
│  Application   (bus, events, validation)     │  ← docile/bus, docile/events, docile/validation
├─────────────────────────────────────────────┤
│  Domain        (your entities, VOs, repos)   │  ← user code; zero framework deps
├─────────────────────────────────────────────┤
│  Infrastructure (routing, config, container) │  ← docile/routing, docile/config, docile/container
├─────────────────────────────────────────────┤
│  Cross-cutting (support, clock, pipeline)    │  ← docile/support
└─────────────────────────────────────────────┘
```

---

## Key design decisions

| Decision | Choice | Reason |
|---|---|---|
| ORM | Doctrine (via `docile/doctrine`) | Full DDD, no active-record magic |
| Container | Compiled autowiring, PSR-11 | Worker-safe, zero runtime reflection in prod |
| HTTP | PSR-7/15 (`nyholm/psr7`) | Runtime-agnostic (FrankenPHP, RoadRunner, SAPI) |
| Testing | PHPUnit 11 + Pest plugin | Standard + ergonomic; PHPStan max |
| Style | PER-CS 2.0 + PHP-CS-Fixer | Enforceable, consistent |
| PHP floor | 8.3 | Readonly classes, `#[\Override]`, `json_validate` |
| Packagist vendor | `docile/*` | Clean, available |

---

## Packages at a glance

| Package | Namespace | Key exports |
|---|---|---|
| `docile/support` | `Docile\Support\` | `Str`, `Arr`, `Collection`, `Pipeline`, `Env`, `SystemClock`, `FrozenClock`, `Optional` |
| `docile/container` | `Docile\Container\` | `Container`, `ContainerInterface`, `#[Inject]`, `#[Singleton]` |
| `docile/config` | `Docile\Config\` | `Repository`, `PhpFileLoader`, `DirectoryLoader`, `ChainLoader` |
| `docile/events` | `Docile\Events\` | `EventDispatcher`, `ListenerProvider`, `StoppableEvent`, `#[AsListener]` |
| `docile/bus` | `Docile\Bus\` | `MessageBus`, `CommandBus`, `QueryBus`, `MapLocator`, `LockingMiddleware` |
| `docile/http` | `Docile\Http\` | `Kernel`, `MiddlewareDispatcher`, `JsonResponse`, `SapiEmitter`, `SecurityHeadersMiddleware` |
| `docile/routing` | `Docile\Routing\` | `Router`, `Matcher`, `UrlGenerator`, `#[Route]`, `#[Get]` … |
| `docile/validation` | `Docile\Validation\` | `Validator`, `ViolationList`, `#[Required]`, `#[Email]` … |
| `docile/console` | `Docile\Console\` | `Kernel`, `Command`, `Input`, `Output`, `ProcessPool` |
| `docile/foundation` | `Docile\Foundation\` | `Application`, `AbstractServiceProvider`, `ExceptionHandler`, bootstrappers |

---

## Runtime safety (worker mode)

Every package in `src/` is free of:
- Static mutable state
- `$_GET`/`$_POST`/`$_SERVER` direct reads (use PSR-7 or `Env`)
- `exit`/`die`/`header()`/`echo` (except `SapiEmitter`)
- `extract()`, variable-variables, `eval`

This makes all packages safe to run under FrankenPHP, RoadRunner, Swoole, and similar
long-running worker runtimes without per-request state leakage.
