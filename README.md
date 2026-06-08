# Novex Trust

> *Codebase paths and internal docs still use the working name "ComplyGCC"; the
> product brand is **Novex Trust**, read from `APP_NAME` (never hardcoded).*

B2B SaaS compliance tracker for UAE businesses: tracks every recurring
government/compliance deadline (trade licence, visas, Emirates ID, labour cards,
Ejari, VAT, corporate tax, WPS, e-invoicing) and stores the underlying documents
in an encrypted vault. Agency-first; GCC-ready architecture with **only the UAE
rule pack active in Phase 1**.

The product brand name is a placeholder and is **never hardcoded** — it is read
from `APP_NAME` via `config('app.name')`. Change it in `backend/.env`.

## Repository layout (monorepo)

```
complygcc/
├── CLAUDE.md            # How we work — standing context for every build session
├── SPEC.md             # Single source of truth for Phase 1 scope
├── uae-rule-data.md    # Verified, sourced UAE rule data (the moat) — corrects SPEC §7
├── README.md           # This file
├── SECURITY.md         # Security posture / review (M10)
├── DEPLOYMENT.md       # Fresh-server deploy guide (Ubuntu 22.04)
├── GO-LIVE.md          # Pre-launch checklist (S3 Singapore, secrets, verify)
├── deploy/             # provision.sh, deploy.sh, nginx/supervisor configs, prod env
└── backend/            # Laravel 12 API + domain core (backend-first; built now)
                        # Future: mobile/ (Flutter) reuses the same REST/JSON API
```

The Laravel app lives in `backend/` (not the repo root) so the planning docs stay
at the top level and there is a clean home for the future Flutter client and any
GCC rule-pack tooling. Flatten to root later if preferred — nothing depends on
the subfolder name.

## Stack (SPEC.md §4)

| Layer        | Choice                                            |
|--------------|---------------------------------------------------|
| Backend      | Laravel 12, PHP 8.4                               |
| Database     | MySQL 8                                            |
| Cache / queue| Redis                                             |
| Queue/scheduler | Laravel queues + scheduler (Supervisor + cron in prod) |
| Web server   | Nginx → PHP-FPM (prod)                             |
| Host         | Ubuntu 24.04 LTS VPS                               |
| Frontend (later) | Blade + Livewire (assumption — SPEC.md §14)   |

## Prerequisites

- **PHP 8.4** with extensions: `openssl`, `mbstring`, `curl`, `fileinfo`,
  `pdo_mysql`, `zip`, `tokenizer`, `ctype`, `json`, `bcmath`, `dom`, `intl`, `gd`.
- **Composer 2.x**
- **MySQL 8** and **Redis** — *not required for milestone 1* (the local `.env`
  uses file/sync drivers so the app boots without them). Stood up in milestone 2.

> On this Windows dev box PHP was installed via winget (`PHP.PHP.8.4`). winget's
> PHP ships without a `php.ini`; one was created from `php.ini-development` and the
> extensions above were enabled. Composer was installed from the signature-verified
> official installer alongside PHP with a `composer.bat` shim.

## Running the backend (local)

```bash
cd backend
composer install                 # if vendor/ is absent
cp .env.example .env             # then set drivers per "Local development" below
php artisan key:generate
php artisan serve                # http://127.0.0.1:8000  (health check: /up)
php artisan test                 # run the test suite
```

### API (milestone 2)

JSON API under `/api`, authenticated with Sanctum bearer tokens:

| Method | Path | Auth | Purpose |
|--------|------|------|---------|
| POST | `/api/register` | public | Create a tenant + owner, return a token |
| POST | `/api/login` | public | Email/password → token |
| GET  | `/api/user` | token | Current user |
| POST | `/api/logout` | token | Revoke the current token |
| GET  | `/api/users` | token | Users in the caller's tenant (platform admin: all) |
| GET  | `/api/users/{id}` | token | One user (cross-tenant id → 404) |
| GET  | `/api/documents` | token | Documents in the caller's tenant |
| POST | `/api/documents` | token | Upload a file → encrypted to the vault (multipart) |
| GET  | `/api/documents/{id}` | token | One document's metadata |
| GET  | `/api/documents/{id}/download` | token | Decrypted bytes (cross-tenant id → 404) |
| POST | `/api/documents/{id}/confirm` | token | Human-confirm gate → generates the deadline |
| GET  | `/api/deadlines` | token | Tenant deadlines (filter `?status=`); cockpit data source |

Seeded demo logins (password `password`): `admin@example.com` (platform admin),
`agency@example.com` (agency owner), `sme@example.com` (SME owner). Run
`php artisan migrate:fresh --seed` to (re)create them.

> **Stopping the dev server on Windows:** `php artisan serve` spawns a child
> `php -S` worker. Killing only the parent leaves the worker holding the port.
> Stop all of them with `Get-Process php | Stop-Process -Force` (or free the port
> via `Get-NetTCPConnection -LocalPort 8000`).

### Local development vs target config

- **`backend/.env.example`** is the committed source of truth and reflects the
  **target stack**: MySQL + Redis (cache/queue/session all on Redis).
- **`backend/.env`** is gitignored and currently a **milestone-1 boot profile**:
  DB is set to MySQL and Redis is configured, but `CACHE_STORE`, `SESSION_DRIVER`
  and `QUEUE_CONNECTION` use `file`/`sync` so the app runs before those services
  exist. In milestone 2, stand up MySQL + Redis and switch those three back to
  `redis` (values are in `.env.example`), then run migrations.

## Coding standards

- **Formatter:** [Laravel Pint](https://laravel.com/docs/pint), config in
  `backend/pint.json` (Laravel preset + `declare(strict_types=1)`, alphabetised
  imports, no unused imports). Run `composer lint` (apply) or
  `composer lint:test` (check only) from `backend/`.
- **Strict types** are required in every PHP file (enforced by Pint).
- **Editor:** `.editorconfig` (4-space indent, LF, trimmed whitespace).
- **Tests accompany each module** — they are not deferred (CLAUDE.md quality bar).
  Milestone 4's deadline-generation logic gets the heaviest test coverage.

## Build status

Following the SPEC.md §12 / CLAUDE.md build order, one milestone at a time:

- [x] **1. Scaffold** — Laravel 12 / PHP 8.4 boots; `.env`, MySQL + Redis config,
  repo structure, coding standards.
- [x] **2. Auth & multi-tenancy** — Sanctum API auth (register/login/logout/me);
  `tenants` + tenant-aware `users`; `UserRole`/`TenantType` enums; strict
  per-tenant isolation via a `TenantScope` global scope + `BelongsToTenant` trait
  + `IdentifyTenant` middleware + `TenantContext`. 22 tests incl. isolation proof.
- [x] **3. Domain models & migrations** — all SPEC §6 entities (entities, persons,
  document_types, documents, deadlines, alerts, rule_packs, audit_logs, consents)
  with relationships, 9 enums, factories, demo seeder. Tenant-owned models carry
  tenancy through; rule_packs/document_types are global. 29 tests.
- [x] **4. UAE rule pack (the moat) + deadline-generation engine** — 14 verified
  document types (UaeRulePackSeeder, incl. e-invoicing ASP 30 Oct 2026 + FTA
  20-business-day corrections); engine (`RenewalCalculator`,
  `DeadlineStatusCalculator`, `UaeWorkingDayCalendar`, `DeadlineGenerator`) with
  data-driven due dates; refuses unconfirmed docs. 64 tests.
- [x] **5. Document vault (envelope encryption)** — per-document AES-256-GCM data
  key wrapped by a KMS master key (`KeyManagementService` + `LocalKms`, key access
  logged); self-contained encrypted envelopes on a config-driven disk; opaque
  `file_ref`, never plaintext to disk; upload/download API (tenant-scoped);
  `php artisan vault:key`. 80 tests.
- [x] **6. Extraction pipeline (interface + stub + human-confirm)** — `Extractor`
  interface + `StubExtractor`; queued `ExtractDocument` job re-fetches bytes from
  the vault (no plaintext through the queue); pipeline pre-fills (`extracted=true`)
  without creating a deadline; `POST /documents/{id}/confirm` is the gate that sets
  `confirmed` + generates the deadline. 87 tests.
- [x] **7. Scheduler & deadlines** — `DeadlineRecomputer` (backfill deadlines for
  confirmed docs + recompute safe/due_soon/overdue, never clobbering `Done`);
  `deadlines:recompute` command scheduled daily (`schedule:run` via cron);
  read-only `GET /api/deadlines`. 96 tests.
- [x] **8. Notifications (email + WhatsApp, queued)** — `NotificationChannel`
  interface + `EmailChannel` (real Mailable) + **`TwilioWhatsappChannel`** (real,
  body or approved Content template; stub fallback when unconfigured) + registry
  (push = drop-in); `AlertMessageBuilder` localizes copy (en/ar) with the
  consequence note; `alerts:send` (hourly) dispatches idempotent `SendAlert` jobs.
  Channels toggled via `COMPLIANCE_ALERT_CHANNELS`.
- [x] **9. Agency cockpit (web)** — Livewire + Tailwind (Vite build); session login;
  traffic-light dashboard (filter, mark-done, assign), entity index + drill-down,
  and **CSV bulk import** of client entities. Tenant-scoped throughout; en/ar lang
  files, RTL-aware. 119 tests.
- [x] **10. Audit, consent, i18n, hardening** — `Auditable` trait + `AuditLogger`
  (mutations + sensitive access + auth); `ConsentService` captures onboarding
  consent incl. cross-border basis; `SetLocale` + en/ar locale switch (RTL);
  security headers + login rate-limiting; consents/audit-logs API; `SECURITY.md`.
  128 tests.
- [x] **11. Deployment** — Ubuntu 22.04 `provision.sh` (PHP 8.4 via ondrej/php,
  MySQL 8, Redis, Nginx, Supervisor, Composer, Node, Certbot), `deploy.sh`
  (composer/npm/migrate/optimize), Nginx + Supervisor configs, scheduler cron,
  `.env.production.example`, HTTPS via Let's Encrypt, and `DEPLOYMENT.md`.
  Route caching verified. 128 tests.
