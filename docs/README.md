# Docile Framework — Design Blueprint

> This folder is the **single source of truth** for the design, decisions, and build plan
> of the Docile framework. It is written so that any contributor (human or AI) can resume
> work with full context, even if prior conversation history is lost.
>
> **If you are an agent resuming this project: read every file in this folder, in order,
> before writing any code.**

## Reading order

| # | File | Purpose |
|---|------|---------|
| 00 | [`00-VISION.md`](./00-VISION.md) | Why Docile exists, who it's for, what makes it different |
| 01 | [`01-DECISIONS.md`](./01-DECISIONS.md) | Locked architectural decisions (ADR log) — **read this first when in doubt** |
| 02 | [`02-ARCHITECTURE.md`](./02-ARCHITECTURE.md) | Runtime model, DDD layering, request/console lifecycles |
| 03 | [`03-MONOREPO.md`](./03-MONOREPO.md) | Repo layout, package list, inter-package dependencies, splitting |
| 04 | [`04-CODING-STANDARDS.md`](./04-CODING-STANDARDS.md) | strict_types, PHPStan max, style, testing/TDD policy |
| 05 | [`05-PACKAGES.md`](./05-PACKAGES.md) | Concrete per-package API specs (classes, method signatures) |
| 06 | [`06-ROADMAP.md`](./06-ROADMAP.md) | Build order, milestones, what's done vs pending |
| 07 | [`07-DEV-ENV.md`](./07-DEV-ENV.md) | Docker toolchain, exact commands to install/test/lint |
| 08 | [`08-PUBLISHING.md`](./08-PUBLISHING.md) | GitHub org, Packagist vendor, release & semver process |

## One-paragraph summary

Docile is a **DDD-first, strict-typed, zero-magic PHP framework** built on PSR standards with
its **own compiled autowiring DI container**, a **runtime-agnostic PSR-7/15 kernel** (classic
PHP-FPM by default, with first-class adapters for FrankenPHP/RoadRunner/Swoole), **Doctrine**
for persistence (SQL + NoSQL), a **CQRS message/event bus**, **driver-based realtime
broadcasting** (Mercure/SSE, Pusher/Ably/Soketi, native WebSocket), **first-class background
jobs & queues**, a **parallel process-pool** for multi-tenant operations, and **secure-by-default**
primitives (RBAC, rate limiting, CSRF, security headers). It is developed as a **monorepo** and
published as à-la-carte `docile/*` packages plus a `docile/framework` meta-package. Everything
is **test-driven with 100% framework line coverage**.

## Status

See [`06-ROADMAP.md`](./06-ROADMAP.md) for the live build checklist.
