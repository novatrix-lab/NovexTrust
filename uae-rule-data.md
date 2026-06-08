# UAE Rule Data — verified baseline (as of June 2026)

> Companion to SPEC.md §7. This is the living, sourced version of the moat data.
> **Re-verify before each seed/release** — UAE compliance dates move (one already
> has, below). Treat anything here as needing a primary-source check (FTA / MoF /
> GDRFA / emirate authority) at build time. Last verified: 2026-06-07.

## Corrections to SPEC.md §7 (verified — use these, not the spec's wording)

### 1. E-invoicing — dates have already shifted
SPEC.md says "large businesses Jan 2027, remaining VAT-registered Jul 2027" and
an ASP-appointment deadline of July 2026. The go-live dates hold; the ASP
deadline moved.

- Voluntary pilot from **1 July 2026** (Ministry-invited participants who agree in writing).
- Large taxpayers (annual revenue **AED 50,000,000+**): ASP appointment deadline
  **30 October 2026** — *extended from 31 July 2026 by an MoF update on 10 May 2026.*
  Mandatory go-live **1 January 2027**.
- Smaller VAT-registered businesses (below AED 50M): ASP by ~**31 March 2027**,
  go-live **1 July 2027**.
- Government entities: go-live **1 October 2027**.
- Scope initially B2B and B2G; B2C out of scope until a later phase.
- Only structured invoices transmitted via an Accredited Service Provider (ASP)
  are valid; PDF/paper are not.
- Penalty regime under Cabinet Decision No. 106 of 2025; figures cited up to
  AED 5,000/month for certain violations — **verify exact penalty schedule before quoting in alert copy.**
- Governing instruments: Ministerial Decisions No. 243 and 244 of 2025 (28 Sep 2025).

**Modelling note:** track e-invoicing as a per-entity *milestone* with a
revenue-threshold branch (≥AED 50M vs <AED 50M) that picks the ASP-by and
go-live dates. Don't hardcode the dates — they belong in the rule pack so the
next shift is a config edit, not a code change.

### 2. "Trade-licence amendment → FTA update" is broader than the spec states
SPEC.md frames this as a licence-amendment-only, 20-working-day trigger. The
actual obligation is wider:

- Under **Federal Decree-Law No. 28 of 2022 on Tax Procedures** and its Cabinet
  Decision (and FTA Public Clarification TAXP007), a registrant must notify the
  FTA within **20 business days** of *any* change to its registered data.
- Covered changes include: legal name / trade name, address, **trade licence
  renewal or amendment**, primary business activity, authorised signatory,
  legal entity type / structure, partnership details.
- Applies to **both VAT and Corporate Tax** registrations.
- Penalties historically AED 5,000 (first failure) / AED 10,000 (subsequent) —
  **verify current schedule.**

**Modelling note:** model this as a *triggered, short-fuse deadline* fired by an
entity-data change event (not only licence amendments): event date + 20 business
days, with the lead/alert schedule compressed accordingly. "Business days"
matters — implement a UAE working-day calendar (Mon–Fri, excluding public
holidays) rather than calendar days.

## Document types to seed (SPEC.md §7, annotated)

Entity-level:
- Trade licence — annual. Consequence: ~3-month lapse can lead the economic
  department to freeze the company record (blocking visa services); banks may
  freeze accounts; landlords may refuse tenancy renewal.
- Establishment / immigration card — periodic; required for visa transactions.
- Ejari (tenancy contract) — annual; prerequisite for trade-licence renewal.
- VAT registration / returns — FTA filing cycle (monthly or quarterly per entity;
  store the cycle per registration). Mandatory VAT registration threshold is
  AED 375,000 turnover (relevant to onboarding logic, not a deadline itself).
- Corporate tax registration / filing — annual obligation (in force since 2023).
- E-invoicing readiness / ASP appointment — phased milestone (see correction 1).
- FTA tax-record update on any registered-data change — 20 business-day trigger
  (see correction 2).

Person-level (per employee / owner / dependent):
- Residence visa — periodic; overstay penalties accrue daily.
- Emirates ID — periodic; tied to the visa.
- Labour card / work permit — periodic.
- WPS (Wages Protection System) — recurring salary-transfer obligation.

## Engine requirements (unchanged from SPEC.md §7)
- Given issue/expiry + the type's lead time, auto-generate the deadline and the
  90/60/30/7/1-day alert schedule.
- Per-jurisdiction variation (mainland vs free zone) via rule-pack data, not
  hardcoded branches.
- Rule packs versioned for auditability.
- UAE rule data kept in structured, easily-editable seed/config.

## Sources (verified 2026-06-07 — re-check before release)
- FTA, Tax Records Amendment (official): https://tax.gov.ae/en/services/tax.records.amendment.2023.aspx
- FTA Public Clarification TAXP007 (summary): https://www.mondaq.com/tax-authorities/1543116/public-clarification-on-updating-information-in-tax-records-with-fta
- E-invoicing ASP deadline extension to 30 Oct 2026 (MoF update): https://www.cleartax.com/ae/e-invoicing-uae
- E-invoicing phase dates (large vs small, govt): https://sscoglobal.com/what-is-the-timeline-for-e-invoicing-in-uae/
- E-invoicing phase 1 / AED 50M threshold: https://gulfnews.com/business/tax-news/uae-e-invoicing-deadline-looms-what-businesses-must-do-before-july-1-1.500527689

> These are secondary/advisory sources used for a fast baseline. For production
> seed data, confirm each value against the primary authority (FTA, MoF, GDRFA,
> emirate DED/free-zone authority) and record the confirmation date next to it.
