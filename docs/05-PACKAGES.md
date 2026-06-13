# 05 — Package API Specs

Concrete shapes the implementation must follow. Signatures are normative (adjust only with a good
reason + doc update). Foundation packages (support, container, http, routing, console) are
specified in detail; later packages are sketched and will be expanded when built.

Legend: all classes `final` unless noted; all files `declare(strict_types=1)`; namespaces under
`Docile\<Package>\`.

---

## docile/support — `Docile\Support\`

- `Str` (static helpers): `studly`, `camel`, `snake`, `kebab`, `title`, `slug`, `plural`,
  `singular`, `startsWith`, `endsWith`, `contains`, `limit`, `random(int): string`,
  `uuid(): string`, `ulid(): string`, `mask`. Pure, no state.
- `Arr` (static): `get(array,'a.b.c',default)`, `set`, `has`, `forget`, `only`, `except`,
  `first`, `last`, `flatten`, `dot`, `undot`, `wrap`, `pluck`.
- `Collection<TKey,TValue>` (immutable, `IteratorAggregate`, `Countable`, `JsonSerializable`):
  `map`, `filter`, `reduce`, `each`, `values`, `keys`, `first`, `last`, `contains`, `sort`,
  `sortBy`, `groupBy`, `chunk`, `toArray`. `@template` annotated for PHPStan generics.
- `Pipeline`: `send($payload)->through(iterable $pipes)->then(Closure)`. Used by middleware/bus.
- `Env`: thin typed reader over `$_ENV`/`$_SERVER` populated by phpdotenv. `get(string,$default)`,
  `bool`, `int`, `string`, `required(string)`. Reads at boot only.
- `Clock` (`Psr\Clock\ClockInterface`): `SystemClock`, `FrozenClock` (testing).
- Optional value objects: `Optional<T>` with `some`/`none`/`map`/`getOrElse`.

## docile/container — `Docile\Container\`

`ContainerInterface extends Psr\Container\ContainerInterface`:
```php
public function bind(string $abstract, Closure|string|null $concrete = null, bool $shared = false): void;
public function singleton(string $abstract, Closure|string|null $concrete = null): void;
public function instance(string $abstract, object $instance): object;
public function alias(string $abstract, string $alias): void;
public function make(string $abstract, array $parameters = []): mixed;     // resolve w/ overrides
public function call(callable|array|string $callback, array $parameters = []): mixed; // method/closure injection
public function has(string $id): bool;       // PSR-11
public function get(string $id): mixed;      // PSR-11
public function scoped(): ContainerInterface; // child scope for request lifetime
```

`Container` (the default impl):
- Reflection autowiring of constructor deps (recursive), resolving by type-hint, falling back to
  default values; throws `BindingResolutionException` for unresolvable primitives without override.
- Singleton cache (`$instances`), aliases, contextual bindings:
  `when(A::class)->needs(Foo::class)->give(Bar::class)`.
- Circular dependency detection → `CircularDependencyException`.
- Attribute support: `#[Inject(id: '...')]` on params, `#[Singleton]` on classes (auto-share).
- `Container::scoped()` returns a child that delegates resolution to the parent but keeps its own
  scoped-singleton cache (worker-mode request isolation).

Exceptions (in `Docile\Container\Exception\`):
- `NotFoundException implements NotFoundExceptionInterface`
- `ContainerException implements ContainerExceptionInterface`
- `BindingResolutionException extends ContainerException`
- `CircularDependencyException extends ContainerException`

Compiled container (later milestone): `ContainerCompiler` emits a PHP class with explicit factory
methods (no runtime reflection) for production/worker mode.

## docile/http — `Docile\Http\`

- PSR-7 impl: depend on `nyholm/psr7` + `nyholm/psr7-server`. Provide:
  - `ServerRequestFactory::fromGlobals(): ServerRequestInterface`
  - `Response`, `JsonResponse`, `RedirectResponse`, `HtmlResponse`, `EmptyResponse`,
    `ProblemResponse` (RFC 7807) — thin factories returning PSR-7 responses.
- `Kernel implements Psr\Http\Server\RequestHandlerInterface`:
  ```php
  public function __construct(ContainerInterface $c, MiddlewareQueue $globalMiddleware, RequestHandlerInterface $router);
  public function handle(ServerRequestInterface $request): ResponseInterface;
  ```
  Builds a `Pipeline` of PSR-15 middleware ending in the router handler.
- `MiddlewareQueue` / `MiddlewareDispatcher implements RequestHandlerInterface`: walks a lazily
  container-resolved list of `MiddlewareInterface`; immutable, worker-safe.
- Built-in middleware: `HandleErrors`, `SecurityHeaders`, `Cors`, `SessionMiddleware`,
  `BodyParser`, `MethodOverride`, `TrailingSlash`. (Some land with `security` package.)
- Emitters: `SapiEmitter::emit(ResponseInterface): void` (classic). Runtime adapters
  (`Adapter\FrankenPHP`, `Adapter\RoadRunner`, `Adapter\Swoole`) live here or in `foundation`.
- Exceptions: `HttpException` (status + headers), `NotFoundHttpException`,
  `MethodNotAllowedHttpException`, `HttpExceptionInterface`.

## docile/routing — `Docile\Routing\`

- Attributes: `#[Route(string $path, array|string $methods = ['GET'], ?string $name = null, array $middleware = [])]`,
  shortcuts `#[Get]`, `#[Post]`, `#[Put]`, `#[Patch]`, `#[Delete]`.
- Fluent API (returns chainable builders):
  ```php
  $r->get('/users/{id}', [UserController::class, 'show'])->name('users.show')->where('id','\d+');
  $r->group(prefix: '/api', middleware: [Auth::class], fn(RouteGroup $g) => ...);
  ```
- `Route` (value object): method(s), compiled pattern, handler, name, params, middleware, wheres.
- `RouteCollection`: add/iterate/named lookup. `Router::match(ServerRequest): MatchedRoute`.
- Matcher: O(1) map for fully-static paths; compiled regex for dynamic. Throws
  `NotFoundHttpException` / `MethodNotAllowedHttpException` (with `Allow` header).
- `UrlGenerator::route(string $name, array $params): string` (reverse routing).
- `RouteRunnerMiddleware` (terminal): resolves controller from container, binds params + validated
  request DTO via container `call()`, returns `ResponseInterface`.
- Route cache: `RouteCollection` compilable to a plain-array file for fast cold boot.

## docile/console — `Docile\Console\`

- `Kernel`: `handle(Input): int`. Resolves command by name from a `CommandRegistry` (container-
  backed). Built-ins: `list`, `help`, `serve`, plus generators (`make:*`) and `orm:*` (via
  doctrine bridge), `queue:work`, `broadcast:*`.
- `Command` (abstract): `#[AsCommand(name: 'group:action', description: '...')]`. Implements
  `configure()` (declare args/options) and `handle(Input,Output): int`. Container-injected deps via
  constructor.
- `Input`: typed access to arguments/options parsed from argv (`argument(name)`, `option(name)`,
  `hasOption`). Definition via `InputArgument`/`InputOption` (name, mode, default, description).
- `Output`: `write`, `writeln`, `line`, `info`, `success`, `warn`, `error`, `table(headers,rows)`,
  `ask`, `confirm`, `secret`. ANSI styling via a `Style` helper; respects `NO_COLOR`/non-TTY.
- **ProcessPool** (headline): `Docile\Console\Process\ProcessPool`:
  ```php
  $pool = new ProcessPool(maxConcurrency: 8);
  foreach ($tenants as $t) {
      $pool->add(new Process(['php','bin/docile','tenant:migrate','--tenant='.$t->id], label: $t->name));
  }
  $result = $pool->run(onProgress: fn(ProcessProgress $p) => $output->updateLine($p));
  // $result: per-process exit codes, durations, captured output; aggregate success/failure.
  ```
  Impl: `proc_open` with non-blocking pipes + `stream_select` loop, concurrency cap, live progress,
  timeout per process, fail-fast or run-to-completion modes. Portable (no pcntl dependency).
- Generators: `make:command`, `make:controller`, `make:entity`, `make:value-object`,
  `make:handler`, `make:migration`, `make:test` — emit DDD-correct stubs.

## docile/events — `Docile\Events\`
- `EventDispatcher implements Psr\EventDispatcher\EventDispatcherInterface`.
- `ListenerProvider` with attribute discovery `#[AsListener(event: X::class, priority: 0)]`.
- Supports stoppable events (PSR-14 `StoppableEventInterface`).

## docile/bus — `Docile\Bus\`
- `CommandBus::dispatch(object $command): mixed`, `QueryBus::ask(object $query): mixed`,
  `EventBus` wraps the PSR-14 dispatcher.
- Handler resolution via `#[CommandHandler]`/`#[QueryHandler]` or map; middleware pipeline
  (validation, transaction, logging, async-routing). Async via `docile/queue` transport.

## docile/validation — `Docile\Validation\`
- Attributes on DTO properties: `#[Required]`, `#[StringType]`, `#[IntType]`, `#[Email]`,
  `#[Length(min,max)]`, `#[Range(min,max)]`, `#[Regex]`, `#[In([...])]`, `#[Confirmed]`, `#[Rule(custom)]`.
- `Validator::validate(object|array $data, string $dtoClass): Result` → DTO or `ValidationException`
  (mapped to 422 problem+json).

## docile/serializer — `Docile\Serializer\`
- Hydrate request → typed DTO; serialize domain → API resource (`Resource`/`ResourceCollection`
  with field selection + relations). Attribute `#[Expose]`, `#[Hidden]`, `#[SerializedName]`.

## docile/security — `Docile\Security\`
- Authn: `Authenticator` drivers (session, token/JWT, api-key). `Identity`/`AuthContext` (scoped).
- Authz: `#[Authorize(policy/permission)]`, `Gate`, `Policy`, voters (RBAC + ABAC). `hash_equals`.
- Hashing: argon2id `PasswordHasher`. CSRF, signed URLs, rate limiter (token bucket), security
  headers middleware. Channel authorization for broadcasting.

## docile/queue — `Docile\Queue\`
- `Job` (`ShouldQueue`), `Dispatcher`, `Worker` (`queue:work`), transports: `sync`, `redis`,
  `amqp`, `sqs`, `database`, `beanstalkd`. Retries/backoff, middleware, failed-job store.

## docile/broadcasting — `Docile\Broadcasting\`
- `Broadcaster` interface; drivers `MercureBroadcaster` (SSE), `PusherBroadcaster`
  (Pusher/Ably/Soketi), `WebSocketBroadcaster`. `Channel` (public/private/presence),
  `ShouldBroadcast` event marker, channel auth via security policies.

## docile/doctrine — `Docile\Doctrine\`
- DBAL/ORM/ODM bootstrapping from typed config; `EntityManager` provider; migration runner
  (`orm:migrate`, integrates with process pool for multi-tenant); `DoctrineRepository` base
  implementing domain repo interfaces; tenant connection resolver.

## docile/foundation — `Docile\Foundation\`
- `Application` (composition root, owns container). `ServiceProvider` abstract
  (`register`, `boot`). `Bootstrappers` (load env, config, providers, register error handler).
  `handleHttp(ServerRequest, ?scope): ResponseInterface`, `handleConsole(Input): int`.
- Default provider set wires http+routing+events+bus+security+config.

## docile/testing — `Docile\Testing\`
- `TestCase` (PHPUnit) + Pest plugin. `ApplicationTestKernel` boots app with test config.
  `HttpTestClient`: `$this->get('/x')`, `->post()`, fluent assertions
  (`assertStatus`, `assertJson`, `assertHeader`). Fakes: `FakeClock`, `FakeBroadcaster`,
  `FakeQueue`, `ArrayCache`, `InMemoryTransport`. `Factory<T>` for building entities/VOs.

## docile/framework (meta) & docile/skeleton
- `framework`: a `composer.json` requiring the curated default packages; no/minimal code.
- `skeleton`: the create-project app (layout in `03-MONOREPO.md`).
