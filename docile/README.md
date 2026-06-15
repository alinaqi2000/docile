# Docile

**A clean, DDD-first PHP framework.**

---

## Requirements

- PHP **8.3** or higher
- Composer **2**

---

## Installation

Create a new project using Composer:

```bash
composer create-project docile-php/docile myapp
cd myapp
cp .env.example .env
```

---

## Starting the Development Server

Docile ships with a built-in `deliver` command that wraps PHP's built-in web server:

```bash
php bin/docile deliver
```

Custom host and port:

```bash
php bin/docile deliver --host=0.0.0.0 --port=8080
```

Or via the Composer script shorthand:

```bash
composer deliver
```

The server starts at **http://localhost:8000** by default. Press `Ctrl+C` to stop.

---

## Running Tests

```bash
composer test
```

---

## Static Analysis

```bash
composer analyse
```

---

## Directory Structure

```
app/
├── Console/Commands/      # Console commands
├── Exceptions/Handler.php # Exception handler
├── Http/
│   ├── Controllers/       # HTTP controllers
│   ├── Kernel.php         # HTTP kernel
│   └── Middleware/        # HTTP middleware
└── Providers/
    └── AppServiceProvider.php

bin/
└── docile                 # CLI entry point

bootstrap/
└── app.php                # Application bootstrap

config/
├── app.php
├── cache.php
└── database.php

public/
└── index.php              # HTTP entry point

routes/
├── api.php
└── web.php

storage/
├── cache/
└── logs/

tests/
└── Feature/
    └── ExampleTest.php
```

---

## Packages

| Package | Description |
|---|---|
| `docile/foundation` | Application, ServiceProvider, ExceptionHandler |
| `docile/container` | PSR-11 dependency injection container |
| `docile/config` | Typed configuration repository |
| `docile/http` | PSR-7/15 HTTP kernel and response factories |
| `docile/routing` | Attribute-based and fluent router |
| `docile/console` | Console kernel, Command, Input, Output |
| `docile/events` | PSR-14 event dispatcher |
| `docile/bus` | CommandBus and QueryBus |
| `docile/validation` | Attribute-based validator |
| `docile/support` | Str, Arr, Collection, Pipeline, Env, Clock, Optional |

---

## License

The Docile framework skeleton is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
