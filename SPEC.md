# Phase 1 Build Requirements — GCC Business Compliance Tracker

*Working name: ComplyGCC (placeholder — to be finalised). Architecture: GCC-ready. Launch market: UAE-first.*
*Version 1.0 — handoff spec for a build session.*

---

## 0. How to use this document (read first)

You are a fresh Claude session being asked to build the backend of this product from scratch and grow it up into a working Phase 1 system. This sheet is the single source of truth — the founder has already made the strategic decisions below; do not re-litigate scope. Your job is to scaffold and build.

Key working agreements:
- **Backend-first.** Build a clean, tested Laravel API and domain core before any UI. A minimal web cockpit comes only after the API is solid.
- **Build incrementally and verify each step.** After each module, confirm it runs and is testable before moving on.
- **Ask the founder only when a decision in Section 14 is genuinely blocking.** Otherwise proceed with the stated assumptions and note them.
- **Do not expand scope.** Anything in the "OUT of Phase 1" list stays out, however tempting.
- Recommend using Claude Code for this build — it is far better suited to scaffolding a full backend than a chat window.

---

## 1. Product summary

A B2B SaaS platform that tracks, stores, and sends alerts for every recurring government/compliance deadline a UAE business faces, with a secure document vault. Sold primarily to PRO firms and corporate service providers (who manage many client entities), with a lighter tier for individual SMEs.

**Core value:** never miss a trade-licence, visa, Emirates-ID, labour-card, Ejari, VAT/tax, WPS, or e-invoicing deadline — and keep all the underlying documents in one encrypted place.

**Strategic frame:** the rules engine and storage are built to support all six GCC countries (country-pluggable), but only the **UAE rule pack is active in Phase 1**. Other countries are configuration to be added later, not a rewrite.

**The moat is the rules engine** (constantly-changing, multi-authority, multi-emirate deadline logic) plus multi-client aggregation — not the reminders, which competitors give away free.

---

## 2. Phase 1 scope

### IN (build this)
- Multi-tenant backend with role-based access (agency, SME, employee, platform admin).
- Multi-entity management (one tenant can hold many business entities; an agency holds many clients).
- UAE compliance rules engine (the document/deadline types in Section 7).
- Deadline scheduler with escalating alerts (90 / 60 / 30 / 7 / 1 days before expiry).
- Notifications: email + WhatsApp Business API (push/FCM optional in Phase 1, design for it).
- Encrypted document vault (per-document encryption, key management separated from data).
- AI document extraction pipeline with a **mandatory human-confirmation step** before any extracted date becomes a tracked deadline.
- Agency cockpit (web): all client entities and their deadlines in one dashboard, traffic-light status.
- Audit log and consent/lawful-basis records.
- English + Arabic (RTL) support designed in from the start.

### OUT of Phase 1 (do not build)
- Any GCC country other than UAE (engine must be *ready* for them, but no other rule packs).
- Automated government filing or fee payment (the product reminds and stores; it does not file).
- Consumer/individual personal-document product as a standalone paid tier.
- Ratings/reviews, marketing CMS, billing/subscription engine beyond a stub (can be added once demand is proven), white-labelling.
- Native mobile apps (responsive web cockpit only in Phase 1; Flutter app is a later phase).

---

## 3. Users & roles

| Role | Description | Key permissions |
|---|---|---|
| Platform admin | The founder/operator | Full system access, manage tenants |
| Agency owner/manager | PRO / service firm | Manage all client entities, all deadlines, all documents within the agency tenant |
| Agency staff | Employees of the agency | Scoped access to assigned clients |
| SME owner | A single business | Manage own entity/entities and documents |
| Employee (read-only) | Staff of an SME | View only their own personal documents (visa, Emirates ID) |

Multi-tenancy rule: data is strictly isolated per tenant. An agency tenant contains many client entities; an SME tenant typically contains one or a few.

---

## 4. Technology stack & environment

- **Backend:** Laravel 12, PHP 8.4
- **Database:** MySQL 8
- **Cache/queue:** Redis
- **Queue workers / scheduler:** Laravel queues + scheduler, run under Supervisor
- **Web server:** Nginx
- **OS / hosting:** Ubuntu 24.04 LTS on a VPS (founder plans an AWS EC2 Ubuntu instance)
- **Frontend (later in Phase 1):** server-rendered Blade + a lightweight JS layer (Livewire or Inertia+Vue — see Section 14), responsive, RTL-capable. Flutter is a future phase.
- **Auth:** Laravel built-in (email/password) + ready to add Google sign-in and phone OTP later.
- **AI extraction:** a bought service (see Section 9) — do not build OCR from scratch.

> Hosting caveat for the founder: the AWS UAE region (me-central-1) and Bahrain (me-south-1) were damaged in the early-2026 regional conflict and may still be unreliable. For the initial VPS, choose a currently-stable region; keep document storage residency-flexible (Section 10) so data location can be changed without re-architecting.

---

## 5. System architecture

Three layers:

1. **Client layer** — Agency cockpit (web), SME portal (web), employee read-only view.
2. **Application layer (Laravel)** — auth & multi-tenant core; the country-pluggable compliance rules engine (UAE active); deadline scheduler; notification service; document extraction pipeline; audit/consent. Cross-cutting: encryption, consent records, transfer safeguards.
3. **Data layer (residency-flexible)** — per-region encrypted document vault (object storage); application database (tenants, entities, deadlines, metadata); KMS/HSM holding keys separately from data; encrypted off-region backup for resilience.

The rules engine must be designed so each country is a self-contained "rule pack" (a set of document types, renewal cycles, lead times, and consequences) that can be enabled per tenant/region. Only UAE ships enabled.

---

## 6. Data model (core entities)

Build these as migrations + Eloquent models. Field lists are the essentials, not exhaustive.

- **tenants** — id, name, type (agency|sme), country (default `AE`), status, created_at.
- **users** — id, tenant_id, name, email, password, role, locale (`en`|`ar`), status.
- **entities** — id, tenant_id, legal_name, trade_name, jurisdiction (mainland|freezone + authority), license_number, country (`AE`), notes. (A "client" for agencies; a "business" for SMEs.)
- **persons** — id, tenant_id, entity_id (nullable), full_name, role (employee|owner|dependent), passport_no, emirates_id_no. (Holds individuals whose documents are tracked.)
- **document_types** — id, country, code, name, category (license|immigration|tax|tenancy|employee|other), default_renewal_cycle, default_lead_days, consequence_note. (Seeded by the rule pack.)
- **documents** — id, tenant_id, entity_id (nullable), person_id (nullable), document_type_id, issue_date, expiry_date, status, file_ref (vault key), extracted (bool), confirmed (bool), confirmed_by, confirmed_at.
- **deadlines** — id, document_id, due_date, lead_days, status (safe|due_soon|overdue|done), responsible_user_id.
- **alerts** — id, deadline_id, channel (email|whatsapp|push), scheduled_for, sent_at, status.
- **rule_packs** — id, country, version, active (bool). (UAE active; others inactive.)
- **audit_logs** — id, tenant_id, user_id, action, target_type, target_id, ip, created_at.
- **consents** — id, tenant_id, user_id, purpose, granted_at, transfer_basis. (Lawful-basis / cross-border-transfer record.)

---

## 7. Compliance rules engine — UAE rule pack (the core IP)

Seed `document_types` for the UAE with at least these. Each has a renewal cycle, a default lead time, and a consequence note (used in alert copy to convey urgency).

> NOTE: Two of the values below have drifted since this spec was written. See the
> companion file `uae-rule-data.md` for the verified, sourced versions (the
> e-invoicing ASP deadline, and the scope of the 20-business-day FTA update rule).
> Use that file's values when seeding.

Entity-level documents:
- **Trade licence** — annual. Consequence: if expired ~3 months, the economy department can freeze the company record, blocking visa services; banks may freeze accounts; landlords may refuse tenancy renewal.
- **Establishment / immigration card** — periodic renewal; required for visa transactions.
- **Ejari (tenancy contract)** — annual; required for trade-licence renewal.
- **VAT registration / returns** — FTA filing cycle (quarterly/monthly per entity).
- **Corporate tax registration / filing** — annual obligation since 2023.
- **E-invoicing readiness / ASP appointment** — phased deadlines (large businesses Jan 2027, remaining VAT-registered Jul 2027); track as a milestone.
- **Trade-licence amendment → FTA update** — when a licence is amended, FTA must be updated within 20 working days (track as a triggered, short-fuse deadline).

Person-level documents (per employee/owner/dependent):
- **Residence visa** — periodic renewal; overstay penalties accrue daily.
- **Emirates ID** — periodic renewal; tied to visa.
- **Labour card / work permit** — periodic renewal.
- **WPS (Wages Protection System)** — recurring salary-transfer compliance obligation.

Engine requirements:
- Given a document's issue/expiry date and its type's lead time, generate the deadline and the alert schedule automatically.
- Support per-jurisdiction variation (mainland vs free zone may differ) via the rule pack, not hardcoded logic.
- Rule packs are versioned so rule changes are auditable.
- Keep the UAE rule data in a structured, easily-editable form (config/seed), because keeping it accurate is the ongoing maintenance burden.

---

## 8. Functional requirements by module

**Auth & multi-tenancy.** Email/password registration and login; tenant creation; role assignment; strict tenant data isolation (global scope on all tenant-owned models). Design auth to allow Google sign-in and phone OTP later without refactor.

**Entity & person management.** CRUD for entities and persons within a tenant. Agencies can manage many entities (clients); SMEs a few. Bulk import (CSV) for agencies onboarding an existing client book — this is critical for agency adoption; a firm will not hand-key 200 clients.

**Document management & vault.** Upload a document file → stored encrypted in the vault (never plaintext on disk). Each document links to an entity or person and a document type, with issue/expiry dates. Files are referenced by an opaque vault key; the app DB never stores the file bytes.

**AI extraction pipeline.** On upload: send the document to the bought extraction service; receive structured fields (type, holder name, ID number, issue/expiry dates); pre-fill the form; **require a human to confirm or correct before the deadline is created.** A misread date must never silently become a live deadline. Mark documents `extracted` and `confirmed` separately.

**Compliance tracking & scheduler.** From confirmed documents, generate deadlines and alert schedules per the rule pack. A daily scheduled job recomputes statuses (safe/due_soon/overdue) and queues due alerts.

**Notifications.** Send alerts via email and WhatsApp Business API on the 90/60/30/7/1-day schedule (configurable per type). Include the consequence note so urgency is clear. Queue all sends; record sent_at and status. Design the notification service so push/FCM is a drop-in channel later.

**Agency cockpit (web).** A dashboard listing all client entities with traffic-light compliance status, sortable/filterable by due date and authority; drill into an entity to see its documents and deadlines; assign a responsible staff member; mark a renewal done. Responsive and RTL-ready.

**Audit & consent.** Log every data-access and mutation action. Capture consent and cross-border-transfer basis at onboarding and store it.

---

## 9. AI extraction — implementation note

Buy, don't build. Use a managed document-AI service (e.g. a cloud OCR/Document-AI service, or a multimodal LLM that reads the image and returns structured fields). Requirements:
- Returns: document type guess, holder name, ID/licence number, issue date, expiry date.
- The vendor must offer **zero data retention / no training on customer data** — this is mandatory because the inputs are passports and Emirates IDs.
- Abstract the extractor behind an interface so the provider can be swapped.
- Per-document cost is small; do not over-optimise it.
- Always human-confirm before a deadline is created (see pipeline above).

---

## 10. Non-functional requirements

**Security & data protection (PDPL-aware).**
- Encrypt documents at rest with application-level (envelope) encryption: a per-document data key, wrapped by a master key held in a KMS/HSM separate from the data store.
- TLS in transit everywhere.
- Keys are the crown jewels — segregate key management from data storage; restrict and log key access.
- Capture lawful basis/consent; store cross-border-transfer basis (SCCs) where data leaves the UAE.
- Breach-ready: logging and the ability to identify affected records.
- The data type here (licences, visas, IDs) is general personal data, not the strict-localisation categories (health/banking) — so in-region storage is a trust choice, not a hard legal mandate. Keep it **residency-flexible**: storage location is configuration, so data can be pinned per country/region later.

**Resilience / DR.** Primary copy in the chosen region; encrypted backup replicated to a second, stable region (the AWS-UAE strike showed single-region risk is real for a document vault). Reconcile DR with residency via encrypted backups under transfer safeguards.

**Internationalisation.** English and Arabic with full RTL. All user-facing strings translatable from day one; do not hardcode copy.

**Multi-tenancy & performance.** Strict per-tenant isolation. Target the modest Phase-1 load comfortably (hundreds of entities per agency, thousands of documents) — this is not a scaling challenge, but indexing and queue hygiene matter.

---

## 11. Hosting & deployment (Ubuntu VPS)

Target: a single Ubuntu 24.04 VPS for Phase 1 (scale out later).
- Nginx as reverse proxy → PHP-FPM (PHP 8.4).
- MySQL 8 and Redis (local or managed).
- Laravel scheduler via cron (`* * * * * php artisan schedule:run`).
- Queue workers under Supervisor for notifications and extraction jobs.
- Object storage for the vault (S3-compatible; choose a stable region — see caveat in Section 4).
- Provide: an automated deploy script, `.env.example`, migration + seeders, and clear setup docs.
- HTTPS via Let's Encrypt.
The build session should produce deployment instructions a non-expert can follow on a fresh Ubuntu box.

---

## 12. Build sequence (milestones for the build session)

Do these in order; verify each runs before the next.

1. **Scaffold.** Fresh Laravel 12 project, `.env`, DB connection, Redis, base config, repo structure, coding standards. Confirm it boots.
2. **Auth & tenancy.** Users, tenants, roles, tenant isolation, login/registration. Tests.
3. **Domain models & migrations.** All entities in Section 6, with relationships, factories, seeders.
4. **UAE rule pack.** Seed `document_types` and the rule logic from Section 7. Unit-test deadline generation.
5. **Document + vault.** Encrypted upload/storage, vault-key referencing, KMS abstraction. Test encryption round-trip.
6. **Extraction pipeline.** Extractor interface + one provider integration + the human-confirm flow.
7. **Scheduler & deadlines.** Daily status recompute; deadline/alert generation from confirmed docs.
8. **Notifications.** Email + WhatsApp channels, queued, scheduled per the alert cadence.
9. **Agency cockpit (minimal web).** Dashboard, entity drill-down, assign/mark-done, CSV bulk import.
10. **Audit, consent, i18n pass, hardening.** Audit logging, consent capture, en/ar RTL, security review.
11. **Deployment.** VPS setup scripts, docs, seed/demo data.

A REST/JSON API should sit under the cockpit so the future Flutter app can reuse it.

---

## 13. First actions for the build session

1. Confirm the stack and the open decisions in Section 14 with the founder (only the blocking ones).
2. Scaffold the Laravel 12 project and get it booting locally.
3. Build auth + multi-tenancy + the data model, with migrations, factories, and seeders.
4. Seed the UAE rule pack and unit-test deadline generation — prove the core engine works before building UI.
Then proceed down Section 12.

---

## 14. Open decisions & assumptions (confirm only if blocking)

- **Project name** — placeholder "ComplyGCC"; founder to finalise.
- **Frontend approach for the cockpit** — assume Laravel + Livewire unless the founder prefers Inertia+Vue.
- **Extraction provider** — assume a zero-retention multimodal LLM or managed Document-AI service; founder to confirm vendor + zero-retention terms.
- **WhatsApp Business API** — assume access will be arranged; build against the interface and stub if access isn't ready yet.
- **Object storage provider/region** — founder to pick a currently-stable region given the AWS-UAE situation; build residency-flexible.
- **Billing** — out of Phase 1 beyond a stub; assume manual invoicing of first customers.

Assumption baseline: where a decision isn't provided, proceed with the assumption above, note it clearly in code/docs, and keep it swappable.

---

*This sheet encodes decisions made during planning: GCC-ready architecture, UAE-first launch, agency-first go-to-market, backend-first build, buy-don't-build extraction, residency-flexible encrypted storage, and a deliberately narrow Phase-1 scope. Build to this; expand later.*
