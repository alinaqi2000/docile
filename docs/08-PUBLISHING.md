# 08 — Publishing: GitHub, Packagist, Releases

## Identifiers
- **GitHub org:** `docile-php` (bare `docile` taken; backup `getdocile`). The monorepo lives at
  `github.com/docile-php/docile`. Each split package gets `github.com/docile-php/<name>`.
- **Composer vendor:** `docile` (verified free on Packagist). Packages: `docile/http`,
  `docile/console`, `docile/framework`, `docile/skeleton`, etc.
- **PHP namespace:** `Docile\` (unaffected by org name).

> The existing personal package `alinaqi2000/docile` (the legacy skeleton) stays as-is for history;
> the new framework publishes under the `docile` vendor.

## One-time setup
1. Create GitHub org `docile-php`; create the monorepo repo `docile` (push current repo there, or
   keep `alinaqi2000/docile` and add `docile-php/docile` as the canonical home — decide at release).
2. Register on Packagist with GitHub login; after the **first** `docile/...` package is submitted,
   open Packagist account settings and **claim/verify the `docile` vendor** so only you can publish
   under it.
3. Enable the Packagist <-> GitHub webhook (auto-update on push/tag) for each published repo.

## Monorepo → many packages (splitting)
- Develop in the monorepo; root `composer.json` uses path repositories:
  ```json
  "repositories": [{ "type": "path", "url": "packages/*", "options": { "symlink": true } }]
  ```
- Use a monorepo splitter (config `monorepo-builder.php`) to:
  - Validate inter-package constraints are in sync (every `docile/*` dependency uses the same
    version range).
  - On tag, **push each `packages/<name>`** to its own read-only repo `docile-php/<name>`.
- Split is automated in `.github/workflows/split.yml` (runs on tag push), using a
  git-subtree-split action with a deploy key/token per target repo.

## Versioning & releases
- **Semantic Versioning.** Pre-1.0: `0.x` may break in minor; document in CHANGELOG.
- All packages share **one synchronized version** (Symfony-style): tagging `v0.2.0` tags every
  package `0.2.0`. monorepo-builder enforces this.
- Branching: `main` (stable), feature branches via PRs. Conventional Commits to drive changelog.
- `CHANGELOG.md` per package (or root aggregated) generated from commits.
- Release flow: green CI → `monorepo-builder release <version>` (bumps, validates) → tag → split
  workflow publishes → Packagist updates via webhook.

## Install UX (what users will run)
```bash
# Full framework app:
composer create-project docile/skeleton my-app
cd my-app && docile serve

# Or à la carte in an existing project:
composer require docile/http docile/routing docile/container
```

## Branding assets (later)
- Reserve `docilephp.com` / `docile.dev` for docs.
- Logo, social handles under the `docile-php` / `getdocile` identity.

## Decision needed from maintainer before first publish
- Confirm GitHub org name (`docile-php` recommended).
- Decide whether monorepo canonical home is `docile-php/docile` (recommended) or stays under the
  personal account initially.
