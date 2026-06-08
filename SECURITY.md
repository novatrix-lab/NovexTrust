# Security posture — ComplyGCC (Phase 1)

A summary of the security review at milestone 10. Re-review before each release.

## Tenancy & access control
- **Strict per-tenant isolation** is enforced centrally by a global Eloquent scope
  (`TenantScope`) + `BelongsToTenant` on every tenant-owned model, driven by a
  request-scoped `TenantContext` set from the authenticated user. Cross-tenant
  reads/writes return 404, proven by tests across every layer (API, cockpit, jobs).
- **Auth:** API uses Sanctum bearer tokens; the cockpit uses the session guard.
  Login is **rate-limited** (`throttle:6,1`) on both. Passwords are bcrypt-hashed.
- **Authorization:** audit trail is restricted to tenant owners + platform admin.

## Data protection (PDPL-aware)
- **Encrypted document vault:** per-document AES-256-GCM data key, **wrapped by a
  KMS master key held separately from the data** (`KeyManagementService` / `LocalKms`).
  The DB stores only an opaque `file_ref`; **plaintext is never written to disk**.
  Envelopes are authenticated (GCM) — tampering is detected on read.
- **Key custody:** `VAULT_MASTER_KEY` is separate from `APP_KEY` and must live
  outside the data store/backups (or use a managed KMS in production). Key access
  (unwrap) is logged.
- **Extraction:** runs on the queue and re-fetches bytes from the vault, so
  plaintext never travels through the queue payload. A real provider MUST be
  zero-retention (passports / Emirates IDs).
- **Consent / lawful basis:** captured at onboarding, including cross-border
  transfer basis (SCCs).
- **Residency:** vault disk + region are configuration (residency-flexible).

## Auditing
- Create/update/delete on user-managed records (entities, persons, documents,
  consents) is auto-audited; sensitive access (document downloads), auth, and
  cockpit deadline actions are audited explicitly. Audit rows capture actor,
  tenant, action, target and IP.

## Transport & headers
- TLS terminates at Nginx in production (Let's Encrypt — M11).
- Baseline response headers: `X-Content-Type-Options`, `X-Frame-Options: DENY`,
  `Referrer-Policy`, `X-Permitted-Cross-Domain-Policies`.

## Known gaps / deferred (Phase 1)
- No full Content-Security-Policy yet (avoid breaking Vite/Livewire) — dedicated pass.
- Read-audit is scoped to sensitive resources, not every query.
- Failed alert deliveries are marked `failed` (no auto-retry/backoff yet).
- `APP_DEBUG=true` is for local only — must be `false` in production.
- MFA / SSO (Google, phone OTP) designed-for but not implemented.
