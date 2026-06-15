# 02 — Architecture

## Layers (DDD)

Docile organizes application code into four layers. The framework enforces the dependency rule:
**dependencies point inward** (Infrastructure → Application → Domain; Presentation → Application).

```
┌─────────────────────────────────────────────────────────────┐
│ Presentation         HTTP controllers, console commands,      │
│                      WebSocket/SSE endpoints, view templates   │
├─────────────────────────────────────────────────────────────┤
│ Application          Use cases: command/query handlers,        │
│                      application services, DTOs, ports         │
├─────────────────────────────────────────────────────────────┤
│ Domain (pure)        Entities, value objects, aggregates,      │
│                      domain events, repository INTERFACES      │
├─────────────────────────────────────────────────────────────┤
│ Infrastructure       Doctrine repositories, queue transports,  │
│                      broadcasters, mailers, external APIs       │
└─────────────────────────────────────────────────────────────┘
```

- **Domain** depends on nothing framework-specific. Plain PHP, strict types, immutable VOs.
- **Application** orchestrates domain via ports (interfaces). Holds command/query handlers.
- **Infrastructure** implements ports (Doctrine, Redis, Mercure, etc.).
- **Presentation** adapts transport (HTTP/CLI/WS) to application use cases.

The framework provides the *plumbing* (container, kernel, routing, buses, etc.) so app authors
only write these four layers. A skeleton app shows the layout (`03-MONOREPO.md`).

## The Application & Kernels

```
Docile\Foundation\Application  (extends/owns the Container)
   ├── boots ServiceProviders (register() then boot())
   ├── holds the typed Config repository
   ├── HTTP Kernel   (Docile\Http\Kernel : PSR-15 RequestHandler)
   └── Console Kernel (Docile\Console\Kernel)
```

- `Application` is the composition root: it builds the container, loads providers, and exposes
  `handleRequest()` (HTTP) and `handle()` (console).
- **Service providers** are the only place that wires services into the container. They are plain
  classes with `register(Container $c): void` and optional `boot(Application $app): void`.

## HTTP request lifecycle (classic runtime)

```
public/index.php
  → require autoload
  → $app = require bootstrap/app.php        (build Application, load providers)
  → $request = ServerRequestFactory::fromGlobals()
  → $response = $app->handleHttp($request)   // Docile\Http\Kernel::handle()
  → (new SapiEmitter)->emit($response)
```

Inside `Docile\Http\Kernel::handle(ServerRequestInterface): ResponseInterface`:

```
[ global middleware pipeline (PSR-15) ]
   error-handling → security headers → session → CORS → body parsing → ...
        → Router middleware:
              match route → resolve route-group middleware
                 → resolve controller from container (autowired)
                    → bind route params + validated DTO
                       → invoke action → ResponseInterface
        ← (response bubbles back up through middleware)
```

- Middleware are PSR-15 `MiddlewareInterface`. The pipeline is a `RequestHandlerInterface` that
  walks a queue (lazy, resolved from container).
- The router produces a `MatchedRoute` (handler + params + group middleware). A
  `RouteRunnerMiddleware` is the terminal handler.
- Controllers are plain classes; actions receive typed args (request DTO, route params, services)
  injected by the container's `call()`.

## Worker runtime (FrankenPHP/RoadRunner/Swoole)

```
worker.php:
  $app = require bootstrap/app.php          // booted ONCE, kept in memory
  loop:
     $request = <runtime provides ServerRequest>
     $scope   = $app->beginScope()          // child container for request-scoped services
     $response = $app->handleHttp($request, $scope)
     <runtime emits $response>
     $scope->end()                          // dispose request-scoped instances
```

Rules that make this safe (enforced by lint/PHPStan + docs):
- No global mutable state in library code; no `static` request state.
- Request-scoped services (current user, request) live in a **scoped container**, never singletons.
- No `exit`/`die`, no `header()`/`echo` in library code — only the emitter writes output.
- Reset side-effecting singletons between requests (clock, RNG seedable in tests).

## Console lifecycle

```
bin/docile (or vendor/bin/docile)
  → $app = require bootstrap/app.php
  → exit($app->handleConsole(new Argv($argv)))
```

`Docile\Console\Kernel`:
```
parse argv → resolve Command (by name, from registry/container)
   → bind arguments/options to typed Input
      → command->run(Input, Output): int (exit code)
```

## Events, buses, and realtime

```
Application code dispatches:
  CommandBus->dispatch(new RegisterUser(...))      // exactly one handler, may be async
  QueryBus->ask(new GetUserById(...))              // exactly one handler, returns a result
  EventBus->dispatch(new UserRegistered(...))      // 0..n listeners (PSR-14)

A domain/integration event implementing ShouldBroadcast is:
  → serialized to a payload
  → published via the configured Broadcaster driver (Mercure/Pusher/WS)
  → delivered to channel subscribers (after channel authorization)
```

Async path: a bus message routed to a transport (`docile/queue`) is encoded, enqueued, and later
consumed by a worker (`docile queue:work`) which re-dispatches it to its handler.

## Multi-tenancy & the process pool

- A **tenant context** resolves the active tenant (by header/subdomain/path) and selects the
  Doctrine connection/database for the request.
- Fleet operations (e.g. migrate every tenant DB) use the **process pool**: one parent command
  spawns up to N child `docile` processes via `proc_open`, multiplexes their stdout/stderr with
  `stream_select`, renders live per-process status, and aggregates exit codes. This is Docile's
  PHP analog to Go's worker/process pools.

## Configuration flow

```
.env  →  (phpdotenv at boot)  →  $_ENV / typed Config repository  →  injected Config objects
```
Runtime code depends on injected, typed config — never on `getenv()` directly.

## Error handling

- A terminal error-handling middleware catches `Throwable`, logs via PSR-3, and renders:
  - APIs: RFC 7807 `application/problem+json`.
  - Web: a friendly error page (debug page only when `APP_ENV=local` / `APP_DEBUG=true`).
- Domain exceptions map to HTTP status via an exception→status map (extensible).
