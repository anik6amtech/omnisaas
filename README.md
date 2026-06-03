# OmniReply

Omnichannel, AI-powered auto-reply platform for f-commerce sellers — answers
WhatsApp, Instagram, and Facebook customers instantly, grounded in the seller's
own catalog and policies, captures orders, and hands off to a human when it
matters. Built to live inside Meta's free 24-hour service window.

> **Status:** Phase 0 — Foundation. This repository currently contains the
> Dockerized skeleton (two planes, tenancy, control panel, queues, realtime,
> dynamic config engine, domain-module contracts). Feature epics (channels,
> pipeline, AI/RAG, inbox, billing) build on top — see `docs/`.

## Architecture in one breath

One Laravel 13 codebase, one PostgreSQL DB, run as **four process roles** from a
single FrankenPHP image (web · workers · websockets · scheduler), split into
**two planes**:

| Plane | Path | Guard | Audience | Built with |
|---|---|---|---|---|
| **Control plane** | `/admin` | `admin` | OmniReply operators | Filament |
| **Tenant plane** | `/app` | `web` | F-commerce sellers | Livewire + Reverb |

A **dynamic config + entitlements engine** (settings + Pennant flags + DB plan
limits) sits between them. Full detail in [`CLAUDE.md`](CLAUDE.md) and
[`docs/`](docs/) (product spec, architecture, dev plan, UX).

**Stack:** Laravel 13 · PHP 8.4 · Livewire 3 + Filament 4 · PostgreSQL 17 +
pgvector · Redis · Horizon · Reverb · Octane + FrankenPHP · Prism (OpenRouter) ·
Sanctum · spatie-permission/settings · Pennant · SSLCommerz · Docker.

## Quick start (Docker)

Requires Docker + Docker Compose.

```bash
git clone <repo-url> omnireply && cd omnireply

cp .env.example .env          # never commit .env
make up                       # backing services: postgres, redis, mailpit, minio, reverb
make key                      # generate APP_KEY (first run only)
make fresh                    # migrate + seed (or: make migrate && make seed)
make dev                      # run the app on the HOST: serve + horizon + vite + logs
```

**Dev model:** Docker runs the lightweight backing services; the app, queue
worker, and Vite run on the **host** via `composer dev` (`make dev`) — fast,
full RAM, **no Octane**. Octane + FrankenPHP is the *production* app server. To
run the entire stack in Docker instead (parity; needs a roomy Docker VM):
`make up-full`.

| Service | URL | Notes |
|---|---|---|
| App (tenant `/app` + control `/admin`) | http://localhost:8000 | `php artisan serve` (host) |
| Control panel | http://localhost:8000/admin | Filament (operators) |
| Horizon (queues) | http://localhost:8000/horizon | operators only (local: open) |
| Reverb (websockets) | ws://localhost:8080 | Docker; realtime inbox |
| Mailpit (mail catcher) | http://localhost:8025 | — |
| MinIO console (S3) | http://localhost:9001 | `omnireply` / `secret1234` |
| Postgres | localhost:**5434** | published off 5432 to avoid clashes |

### Seeded logins (local only — change everywhere else)

| Role | URL | Email | Password |
|---|---|---|---|
| Operator (admin guard) | `/admin` | `admin@omnireply.test` | `password` |
| Seller (web guard) | `/app` *(E1)* | `seller@omnireply.test` | `password` |

The seed also creates a `Demo Store` workspace and the operator RBAC roles
(Super Admin / Support / Billing / Content Editor).

## Common commands

```bash
make up / up-full / down / ps / logs   # stack lifecycle (lean vs full Docker)
make dev                               # run the app on the host (composer dev)
make migrate / fresh / seed            # database (host)
make test                              # Pest suite (host, Postgres-backed)
make pint                              # format (PSR-12)
make stan                              # Larastan (level 5)
make horizon                           # run Horizon in the foreground (host)
```

Run `make help` for the full list.

## Quality gates

`vendor/bin/pint` (format) · `vendor/bin/phpstan analyse` (Larastan L5) ·
`php artisan test` (Pest). All three run in CI (`.github/workflows/ci.yml`)
against Postgres+pgvector and Redis service containers, plus a Docker image
build.

## Project structure

```
app/
  Domain/<Module>/   bounded contexts (Tenancy, Channels, Inbox, Messaging, AI,
                     Knowledge, Catalog, Orders, Billing, Analytics) — each with
                     a README; behavior lives in Actions/Services, never in
                     controllers/components/jobs.
  Models/            Eloquent models (User, AdminUser, Workspace, + E2/E3 stubs)
  Filament/          control-plane resources & pages
  Settings/ Features/ dynamic config (spatie settings) + feature flags (Pennant)
docs/                authoritative product spec, architecture, dev plan, UX
docker/              Postgres init (creates the test DB)
```

## Configuration & secrets

`.env` holds master secrets (`APP_KEY`, DB creds) and is never committed.
Panel-editable *operational* secrets (provider keys, Meta tokens) are encrypted
at rest in the DB — never in `.env` or the UI. See `CLAUDE.md` → Conventions.

## License

Proprietary. © OmniReply.
