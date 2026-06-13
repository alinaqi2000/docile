# 00 — Vision

## The dream

Docile is a PHP framework for building **highly secure, role-based, real-time backends and
APIs** that scale to **millions of users**, while staying **minimal to learn and install**. It
should be the default choice for any business domain — from a tiny microservice to a large
multi-tenant SaaS — and become a new industry standard alongside (and ahead of) Symfony and
Laravel.

Originally written by the author as a junior dev (a small MVC microframework), Docile is being
rebuilt from the ground up by the same author now as a senior engineer / tech lead.

## Target users

- **Backend & API teams** who want DDD, type-safety, and security without ceremony.
- **Microservice authors** who need a small, fast, composable core.
- **Realtime app builders** (dashboards, chat, collaboration, live data) who want push updates
  built in, not bolted on.
- **Multi-tenant SaaS operators** who run fleet-wide operations (e.g. migrations across hundreds
  of tenant databases) and need parallelism.
- **Full-stack developers** who occasionally want server-rendered views too.

## Core principles

1. **Zero magic, fully explicit.** No facades, no global mutable state, no runtime string-magic.
   Everything is constructor-injected and discoverable by your IDE and by PHPStan.
2. **DDD-first.** The framework keeps the Domain layer pure. Persistence (Doctrine), transport
   (HTTP), and delivery (realtime) are infrastructure concerns that never leak into the domain.
3. **Strict types everywhere.** `declare(strict_types=1)` in every file, PHPStan at max level,
   immutable value objects, `final` by default.
4. **PSR-native.** PSR-4, PSR-7/15/17 (HTTP), PSR-11 (container), PSR-14 (events), PSR-3 (log),
   PSR-6/16 (cache). Interop is a feature.
5. **Attribute-driven DX.** Routes, DI wiring, event listeners, queued jobs, validation, and
   authorization are declared with PHP 8 attributes — concise and type-checked, not YAML.
6. **Runtime-agnostic.** The same app code runs under classic PHP-FPM, FrankenPHP worker mode,
   RoadRunner, or Swoole. Scaling is a deployment choice, not a rewrite.
7. **Secure by default.** Safe defaults for headers, sessions, CSRF, password hashing, rate
   limiting, and RBAC. You opt *out* of safety, never *into* it.
8. **Test-driven.** The framework itself has 100% line coverage. App developers get a first-class
   testing kernel and HTTP client so TDD is the path of least resistance.
9. **Minimal to start, scalable to huge.** `composer create-project docile/skeleton app` and you
   have a running, secure API in seconds. Add packages only as you need them.

## Where Symfony and Laravel frustrate developers — and Docile's answer

| Pain point | Laravel | Symfony | Docile's answer |
|---|---|---|---|
| Hidden global state | Facades + helpers everywhere | mostly avoided | **No facades/globals.** Pure DI, PSR-11. |
| ORM couples domain to DB | Eloquent (ActiveRecord) | Doctrine (good) | **Doctrine + pure domain** repositories. |
| Runtime "magic" hurts IDE/PHPStan | `__get`/`__call` heavy | moderate | **Attributes + explicit types**, PHPStan max. |
| Config verbosity | OK | YAML/XML ceremony | **PHP + attributes**, typed config objects. |
| Onboarding curve | gentle | steep | **Minimal core + generators + great docs.** |
| Realtime is an add-on | Echo/Reverb add-on | bring-your-own | **First-class broadcasting** bound to events. |
| Scaling to async | Octane add-on | Runtime component | **Runtime-agnostic kernel** from day one. |
| Fleet/multi-tenant ops | manual | manual | **Built-in parallel process pool.** |
| Test ergonomics | good | good | **Testing kernel + HTTP client + factories**, 100% covered core. |

## Non-goals

- Not a frontend/JS framework. We provide server rendering + realtime push; SPA frameworks plug in.
- Not a CMS. Docile is a foundation for building applications.
- We will not chase "clever" features that compromise type-safety, security, or testability.

## Naming & branding

- Framework name: **Docile**. CLI binary: `docile`.
- PHP root namespace: `Docile\`.
- Composer vendor: `docile/*` (e.g. `docile/http`, `docile/framework`). Verified available on
  Packagist.
- GitHub organization: **`docile-php`** (the bare `docile` org is taken; this does NOT affect
  package names or namespaces). Backup: `getdocile`.
- We keep a tasteful amount of the original "playful" branding (e.g. `docile serve`) but the
  public API is professional and predictable.
