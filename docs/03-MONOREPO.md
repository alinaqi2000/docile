# 03 — Monorepo Layout & Packages

## Repository tree (target)

```
docile/                                   # repo root (monorepo) — github.com/docile-php/docile
├── ARCHITECTURE.md                        # concise root pointer to docs/
├── README.md
├── CONTRIBUTING.md
├── LICENSE                                # MIT
├── composer.json                          # root: type=project, path repos, dev tooling
├── monorepo-builder.php                   # split config (package versions, sync)
├── phpunit.xml.dist                        # aggregates packages/*/tests
├── phpstan.neon.dist                       # level: max
├── .php-cs-fixer.dist.php                  # PER-CS / PSR-12 + strict
├── infection.json5.dist                    # mutation testing config
├── Dockerfile                              # dev/CI image (php 8.3 + composer + pcov + ext)
├── docker-compose.yml                      # dev services (app, db, redis, mercure)
├── docker/                                 # php.ini, entrypoints, Caddyfile (FrankenPHP)
├── bin/
│   └── docile                              # console entrypoint for monorepo dev
├── .github/workflows/                      # ci.yml (test matrix), static.yml, split.yml
├── docs/                                   # THIS design blueprint
├── packages/
│   ├── contracts/        → docile/contracts
│   ├── support/          → docile/support
│   ├── container/        → docile/container
│   ├── config/           → docile/config
│   ├── http/             → docile/http
│   ├── routing/          → docile/routing
│   ├── console/          → docile/console
│   ├── events/           → docile/events
│   ├── bus/              → docile/bus
│   ├── validation/       → docile/validation
│   ├── serializer/       → docile/serializer
│   ├── security/         → docile/security
│   ├── queue/            → docile/queue
│   ├── broadcasting/     → docile/broadcasting
│   ├── doctrine/         → docile/doctrine
│   ├── cache/            → docile/cache
│   ├── log/              → docile/log
│   ├── testing/          → docile/testing
│   ├── openapi/          → docile/openapi
│   ├── foundation/       → docile/foundation   (Application, providers, kernels glue)
│   └── framework/        → docile/framework     (meta-package requiring the above)
└── skeleton/             → docile/skeleton       (create-project app template)
```

Each `packages/<name>/` contains:
```
packages/<name>/
├── composer.json        # name: docile/<name>, psr-4 Docile\<Name>\ → src/
├── src/
└── tests/
```

## Package responsibilities & dependencies

Dependency arrows = "depends on". Aim for a thin, acyclic graph.

| Package | Purpose | Depends on (Docile) | Key external |
|---|---|---|---|
| `contracts` | Shared interfaces not owned by a single package | — | psr/* |
| `support` | Str, Arr, Collection, Env, Pipeline, value objects, Clock | contracts | — |
| `container` | PSR-11 autowiring + compiled container | contracts | psr/container |
| `config` | Typed config repository, loaders | support | — |
| `http` | PSR-7/17 bridge, Kernel, middleware pipeline, emitters, adapters | contracts, container | psr/http-*, nyholm/psr7 |
| `routing` | Attribute + fluent routing, matcher, URL generator | http, support | — |
| `events` | PSR-14 dispatcher, listener providers, `#[AsListener]` | contracts, container | psr/event-dispatcher |
| `bus` | Command/Query/Event buses, CQRS, middleware | events, container | — |
| `validation` | Attribute validation rules + validator | support | — |
| `serializer` | DTO hydration + API resource serialization | support | — |
| `security` | Auth, RBAC/ABAC, policies, hashing, CSRF, rate limit, headers | http, container, events | — |
| `cache` | PSR-6/16 cache, drivers (array, file, redis) | contracts | psr/cache, psr/simple-cache |
| `log` | PSR-3 logging, Monolog bridge | contracts | psr/log, monolog/monolog |
| `queue` | Job abstraction + transports (sync/redis/amqp/sqs/db/beanstalk) | bus, serializer | (driver libs, suggest) |
| `broadcasting` | Broadcaster + drivers (Mercure/SSE, Pusher, WebSocket) | events, http, security | (driver libs, suggest) |
| `doctrine` | DBAL/ORM/ODM bridge, migrations, multi-tenant, repo base | container, config | doctrine/orm, doctrine/dbal |
| `console` | Console kernel, commands, ProcessPool, generators | container, support | — |
| `testing` | TestCase, app/HTTP test kernel, fakes, factories | foundation, http | phpunit (require-dev/peer) |
| `openapi` | OpenAPI 3.1 generation from attributes | routing, validation | — |
| `foundation` | Application, ServiceProvider, bootstrapping, exception handler | container, http, console, config, events | — |
| `framework` | Meta-package: requires the curated default set | (all above) | — |
| `skeleton` | `create-project` app template (DDD layout) | framework | — |

## Skeleton app layout (what users get)

```
my-app/
├── composer.json                 # requires docile/framework
├── public/index.php              # SAPI entrypoint
├── worker.php                    # FrankenPHP/RoadRunner/Swoole entrypoint (optional)
├── bin/docile                    # console entrypoint
├── bootstrap/app.php             # builds Application, registers providers
├── .env / .env.example
├── config/                       # app.php, database.php, security.php, broadcasting.php, ...
├── routes/                       # http.php, console.php, channels.php
├── src/
│   ├── Domain/                   # entities, VOs, domain events, repo interfaces (pure)
│   ├── Application/              # commands, queries, handlers, DTOs, ports
│   ├── Infrastructure/           # Doctrine repos, transports, external adapters
│   └── Presentation/
│       └── Http/Controllers/     # controllers (thin)
└── tests/                        # Unit / Integration / Feature
```

## Splitting & publishing

- Local dev: root `composer.json` uses `"repositories": [{ "type": "path", "url": "packages/*" }]`
  with symlinks, so cross-package changes are instant.
- Release: monorepo-builder validates that every package's `composer.json` inter-dependency
  constraints are in sync, bumps versions atomically, tags, and pushes each `packages/*` to its
  own read-only repo (`docile-php/<name>`) which is registered on Packagist.
- See `08-PUBLISHING.md` for the exact flow.
