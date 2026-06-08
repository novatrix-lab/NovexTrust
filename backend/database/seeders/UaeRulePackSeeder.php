<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\DocumentCategory;
use App\Enums\DocumentLevel;
use App\Enums\RenewalCycle;
use App\Models\DocumentType;
use App\Models\RulePack;
use Illuminate\Database\Seeder;

/**
 * The UAE rule pack — the product's moat (SPEC.md §7, with verified values from
 * uae-rule-data.md, last verified 2026-06-07). Re-verify against primary sources
 * (FTA / MoF / GDRFA / emirate authority) before each release.
 *
 * Values are structured data on purpose (CLAUDE.md "rule data is a living
 * asset"): editing a date or cadence is a data change, never a code change.
 */
class UaeRulePackSeeder extends Seeder
{
    public function run(): void
    {
        $pack = RulePack::query()->updateOrCreate(
            ['country' => 'AE', 'version' => config('compliance.uae_rule_pack_version')],
            ['active' => true],
        );

        foreach ($this->documentTypes() as $type) {
            DocumentType::query()->updateOrCreate(
                [
                    'rule_pack_id' => $pack->id,
                    'code' => $type['code'],
                    'jurisdiction' => $type['jurisdiction'] ?? null,
                ],
                array_merge(['country' => 'AE'], $type),
            );
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function documentTypes(): array
    {
        return [
            // ---- Entity-level -------------------------------------------------
            [
                'code' => 'trade_licence',
                'name' => 'Trade Licence',
                'category' => DocumentCategory::License,
                'level' => DocumentLevel::Entity,
                'default_renewal_cycle' => RenewalCycle::Annual,
                'default_lead_days' => 90,
                'consequence_note' => 'Annual renewal. If expired ~3 months the economic department can freeze the company record (blocking visa services); banks may freeze accounts; landlords may refuse tenancy renewal.',
            ],
            [
                'code' => 'establishment_card',
                'name' => 'Establishment / Immigration Card',
                'category' => DocumentCategory::Immigration,
                'level' => DocumentLevel::Entity,
                'default_renewal_cycle' => RenewalCycle::Annual,
                'default_lead_days' => 60,
                'consequence_note' => 'Required for all visa transactions; a lapse blocks visa processing for staff.',
            ],
            [
                'code' => 'ejari',
                'name' => 'Ejari (Tenancy Contract)',
                'category' => DocumentCategory::Tenancy,
                'level' => DocumentLevel::Entity,
                'default_renewal_cycle' => RenewalCycle::Annual,
                'default_lead_days' => 60,
                'consequence_note' => 'Annual; a valid Ejari is a prerequisite for trade-licence renewal.',
            ],
            [
                'code' => 'vat_return',
                'name' => 'VAT Return',
                'category' => DocumentCategory::Tax,
                'level' => DocumentLevel::Entity,
                // Filing is monthly or quarterly per registration; quarterly is the
                // common default. Store the actual cycle per document/registration.
                'default_renewal_cycle' => RenewalCycle::Quarterly,
                'default_lead_days' => 30,
                'consequence_note' => 'Late VAT filing/payment incurs FTA administrative penalties; persistent default can escalate.',
            ],
            [
                'code' => 'corporate_tax_return',
                'name' => 'Corporate Tax Registration / Filing',
                'category' => DocumentCategory::Tax,
                'level' => DocumentLevel::Entity,
                'default_renewal_cycle' => RenewalCycle::Annual,
                'default_lead_days' => 90,
                'consequence_note' => 'Annual obligation (in force since 2023). Late registration/filing incurs FTA penalties.',
            ],
            [
                // E-invoicing milestones — fixed calendar dates (uae-rule-data.md
                // correction 1). Revenue-threshold branch: >= AED 50M vs < AED 50M.
                'code' => 'einvoicing_asp_large',
                'name' => 'E-invoicing ASP Appointment (revenue ≥ AED 50M)',
                'category' => DocumentCategory::Tax,
                'level' => DocumentLevel::Entity,
                'default_renewal_cycle' => RenewalCycle::OneTime,
                'default_lead_days' => 90,
                'fixed_due_date' => '2026-10-30',
                'consequence_note' => 'Large taxpayers (revenue ≥ AED 50M): appoint an Accredited Service Provider by 30 Oct 2026 (extended from 31 Jul 2026). Only ASP-transmitted structured invoices are valid. Penalty regime under Cabinet Decision 106/2025 — verify exact schedule before quoting.',
            ],
            [
                'code' => 'einvoicing_golive_large',
                'name' => 'E-invoicing Go-live (revenue ≥ AED 50M)',
                'category' => DocumentCategory::Tax,
                'level' => DocumentLevel::Entity,
                'default_renewal_cycle' => RenewalCycle::OneTime,
                'default_lead_days' => 60,
                'fixed_due_date' => '2027-01-01',
                'consequence_note' => 'Mandatory e-invoicing go-live on 1 Jan 2027 for businesses with revenue ≥ AED 50M.',
            ],
            [
                'code' => 'einvoicing_asp_small',
                'name' => 'E-invoicing ASP Appointment (revenue < AED 50M)',
                'category' => DocumentCategory::Tax,
                'level' => DocumentLevel::Entity,
                'default_renewal_cycle' => RenewalCycle::OneTime,
                'default_lead_days' => 90,
                'fixed_due_date' => '2027-03-31',
                'consequence_note' => 'Smaller VAT-registered businesses (revenue < AED 50M): appoint an ASP by ~31 Mar 2027.',
            ],
            [
                'code' => 'einvoicing_golive_small',
                'name' => 'E-invoicing Go-live (revenue < AED 50M)',
                'category' => DocumentCategory::Tax,
                'level' => DocumentLevel::Entity,
                'default_renewal_cycle' => RenewalCycle::OneTime,
                'default_lead_days' => 60,
                'fixed_due_date' => '2027-07-01',
                'consequence_note' => 'Go-live on 1 Jul 2027 for businesses with revenue < AED 50M.',
            ],
            [
                // FTA tax-record update — triggered, short-fuse: 20 BUSINESS days
                // from any registered-data change (uae-rule-data.md correction 2).
                'code' => 'fta_record_update',
                'name' => 'FTA Tax-Record Update (on registered-data change)',
                'category' => DocumentCategory::Tax,
                'level' => DocumentLevel::Entity,
                'default_renewal_cycle' => RenewalCycle::Custom,
                'default_lead_days' => 5,
                'business_day_offset' => 20,
                'alert_offset_days' => [10, 5, 2, 1], // compressed for the short fuse
                'consequence_note' => 'Notify the FTA within 20 business days of any change to registered data (legal/trade name, address, trade-licence renewal/amendment, activity, authorised signatory, legal structure) — applies to both VAT and Corporate Tax. Penalties historically AED 5,000 then AED 10,000 — verify current schedule.',
            ],
            [
                'code' => 'wps',
                'name' => 'WPS (Wages Protection System)',
                'category' => DocumentCategory::Employee,
                // Modelled at entity level: WPS is a recurring monthly employer
                // salary-transfer obligation, not a per-person document. (The
                // source groups it with person docs; this is the truer modelling.)
                'level' => DocumentLevel::Entity,
                'default_renewal_cycle' => RenewalCycle::Monthly,
                'default_lead_days' => 7,
                'consequence_note' => 'Salaries must be paid via the Wages Protection System each cycle; non-compliance can suspend new work permits and incur MOHRE penalties.',
            ],

            // ---- Person-level -------------------------------------------------
            [
                'code' => 'residence_visa',
                'name' => 'Residence Visa',
                'category' => DocumentCategory::Immigration,
                'level' => DocumentLevel::Person,
                'default_renewal_cycle' => RenewalCycle::Biennial,
                'default_lead_days' => 90,
                'consequence_note' => 'Overstay penalties accrue daily after expiry; renew before expiry to avoid fines and status issues.',
            ],
            [
                'code' => 'emirates_id',
                'name' => 'Emirates ID',
                'category' => DocumentCategory::Immigration,
                'level' => DocumentLevel::Person,
                'default_renewal_cycle' => RenewalCycle::Biennial,
                'default_lead_days' => 60,
                'consequence_note' => 'Tied to the residence visa; required for most government and banking transactions.',
            ],
            [
                'code' => 'labour_card',
                'name' => 'Labour Card / Work Permit',
                'category' => DocumentCategory::Employee,
                'level' => DocumentLevel::Person,
                'default_renewal_cycle' => RenewalCycle::Biennial,
                'default_lead_days' => 60,
                'consequence_note' => 'The MOHRE work permit must be valid to employ staff legally; a lapse exposes the employer to fines.',
            ],
        ];
    }
}
