# CLAUDE.md — ComplyGCC (placeholder name)

> This file is standing context for every Claude Code session in this repo.
> Read it and `SPEC.md` in full before doing anything. `SPEC.md` is the
> single source of truth for scope; this file is *how we work* plus a few
> verified corrections. If the two ever conflict, ask — don't guess.

## What this is
A B2B SaaS compliance tracker for UAE businesses: tracks every recurring
government/compliance deadline (trade licence, visas, Emirates ID, labour
cards, Ejari, VAT, corporate tax, WPS, e-invoicing) and stores the underlying
documents in an encrypted vault. Sold agency-first (PRO / corporate service
firms managing many client entities), lighter tier for SMEs.

The moat is the **rules engine** + multi-client aggregation, not the reminders.
Architecture is **GCC-ready** but only the **UAE rule pack ships active** in
Phase 1. Other countries are configuration to add later, never a rewrite.

## Working agreements (do not violate)
1. **Backend-first.** Clean, tested Laravel API + domain core before any UI.
   The web cockpit comes only after the API is solid.
2. **Incremental + verify.** Build one milestone, prove it runs and is testable,
   *then* move on. Never jump ahead. Stop after each milestone for review.
3. **Do not expand scope.** Everything in SPEC.md §2 "OUT of Phase 1" stays out,
   however tempting. No automated filing, no payments, no native apps, no
   billing engine beyond a stub.
4. **Ask only when genuinely blocked.** SPEC.md §14 lists open decisions. The
   assumption baseline below unblocks every one of them for milestones 1–8.
   Only surface a §14 item if it actually blocks the milestone in front of you.
5. **A REST/JSON API sits under the cockpit** so the future Flutter app reuses it.

## Stack (confirmed)
- Laravel 12, PHP 8.4
- MySQL 8, Redis (cache + queue)
- Queue workers + scheduler under Supervisor; scheduler via cron
- Nginx → PHP-FPM
- Ubuntu 24.04 LTS VPS (founder plans AWS EC2; pick a *currently-stable* region)
- Frontend (later): Blade + Livewire (assumption — see below), responsive, RTL
- Auth: Laravel email/password, designed to add Google sign-in + phone OTP later
- AI extraction: a **bought** zero-retention service behind an interface — never build OCR

## Assumption baseline (SPEC.md §14 — proceed on these, note them in code/docs, keep swappable)
- **Name:** "ComplyGCC" is a placeholder. Don't hardcode it; use a config value.
- **Cockpit frontend:** Laravel + Livewire unless founder says Inertia+Vue. Irrelevant until milestone 9.
- **Extraction provider:** zero-retention multimodal LLM or managed Document-AI. Build behind an `Extractor` interface; ship a stub provider so the pipeline is testable without a vendor.
- **WhatsApp Business API:** build to a `NotificationChannel` interface; stub it if access isn't live yet.
- **Object storage:** S3-compatible, residency-flexible (region is config). Pick a stable region given the AWS me-central-1 / me-south-1 instability.
- **Billing:** stub only. Assume manual invoicing of first customers.

## Build order (SPEC.md §12 — verify each runs before the next)
1. Scaffold — fresh Laravel 12, .env, DB + Redis, repo structure, standards. Confirm it boots.
2. Auth & multi-tenancy — users, tenants, roles, strict tenant isolation (global scope on all tenant-owned models), login/registration. Tests.
3. Domain models & migrations — all entities in SPEC.md §6, relationships, factories, seeders.
4. **UAE rule pack** — seed `document_types` + deadline logic from SPEC.md §7 and `uae-rule-data.md`. Unit-test deadline generation. **Prove the core engine before any UI.**
5. Document + vault — envelope encryption, opaque vault-key referencing, KMS abstraction. Test the encryption round-trip; never write plaintext to disk.
6. Extraction pipeline — `Extractor` interface + one provider + the **mandatory human-confirm step**. `extracted` and `confirmed` are separate flags; a misread date must never silently become a live deadline.
7. Scheduler & deadlines — daily status recompute (safe/due_soon/overdue), deadline + alert generation from *confirmed* docs only.
8. Notifications — email + WhatsApp channels, queued, on the 90/60/30/7/1-day cadence (configurable per type), consequence note included. Design push/FCM as a drop-in channel.
9. Agency cockpit (minimal web) — dashboard with traffic-light status, entity drill-down, assign/mark-done, CSV bulk import (critical for agency onboarding). Responsive + RTL.
10. Audit, consent, i18n pass, hardening — audit every access/mutation, capture consent + cross-border-transfer basis, full en/ar RTL with no hardcoded copy, security review.
11. Deployment — VPS setup scripts, .env.example, migrations + seeders, docs a non-expert can follow on a fresh Ubuntu box, HTTPS via Let's Encrypt.

## Quality bar
- Tests accompany each module (don't defer them). Milestone 4's deadline-generation logic is the highest-value thing to test thoroughly — it's the moat.
- Strict per-tenant isolation is a security property, not a convenience. Test that one tenant cannot read another's data.
- All user-facing strings translatable from day 1 (en + ar, RTL). No hardcoded copy.
- Keys are the crown jewels: key management must be separable from data storage and access-logged.

## Rule data is a living asset
The UAE rule data is the product's moat and it changes often. Keep it in
structured, easily-editable seed/config — not buried in code branches. Version
rule packs (`rule_packs.version`) so changes are auditable. **Verify rule values
against primary sources (FTA, MoF, GDRFA, the relevant emirate authority) before
seeding** — see `uae-rule-data.md`, which already corrects two values that
drifted since SPEC.md was written.
