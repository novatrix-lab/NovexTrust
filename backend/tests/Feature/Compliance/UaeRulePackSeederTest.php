<?php

declare(strict_types=1);

namespace Tests\Feature\Compliance;

use App\Enums\DocumentLevel;
use App\Enums\RenewalCycle;
use App\Models\RulePack;
use Database\Seeders\UaeRulePackSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UaeRulePackSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_an_active_uae_pack_with_document_types(): void
    {
        $this->seed(UaeRulePackSeeder::class);

        $pack = RulePack::activeFor('AE');

        $this->assertNotNull($pack);
        $this->assertTrue($pack->active);
        $this->assertSame(14, $pack->documentTypes()->count());
    }

    public function test_trade_licence_has_verified_values(): void
    {
        $this->seed(UaeRulePackSeeder::class);
        $type = RulePack::activeFor('AE')->documentType('trade_licence');

        $this->assertSame(RenewalCycle::Annual, $type->default_renewal_cycle);
        $this->assertSame(90, $type->default_lead_days);
        $this->assertSame(DocumentLevel::Entity, $type->level);
        $this->assertStringContainsString('freeze the company record', $type->consequence_note);
    }

    public function test_einvoicing_milestones_use_verified_fixed_dates(): void
    {
        $this->seed(UaeRulePackSeeder::class);
        $pack = RulePack::activeFor('AE');

        // Correction 1 (uae-rule-data.md): ASP deadline extended to 30 Oct 2026.
        $this->assertSame('2026-10-30', $pack->documentType('einvoicing_asp_large')->fixed_due_date->toDateString());
        $this->assertSame('2027-01-01', $pack->documentType('einvoicing_golive_large')->fixed_due_date->toDateString());
        $this->assertSame('2027-03-31', $pack->documentType('einvoicing_asp_small')->fixed_due_date->toDateString());
        $this->assertSame('2027-07-01', $pack->documentType('einvoicing_golive_small')->fixed_due_date->toDateString());
    }

    public function test_fta_record_update_is_a_twenty_business_day_trigger(): void
    {
        $this->seed(UaeRulePackSeeder::class);
        $type = RulePack::activeFor('AE')->documentType('fta_record_update');

        // Correction 2 (uae-rule-data.md): 20 business days, broad scope.
        $this->assertSame(20, $type->business_day_offset);
        $this->assertSame([10, 5, 2, 1], $type->alert_offset_days);
        $this->assertSame(RenewalCycle::Custom, $type->default_renewal_cycle);
    }

    public function test_person_level_documents_are_present(): void
    {
        $this->seed(UaeRulePackSeeder::class);
        $pack = RulePack::activeFor('AE');

        $this->assertSame(DocumentLevel::Person, $pack->documentType('residence_visa')->level);
        $this->assertSame(RenewalCycle::Biennial, $pack->documentType('emirates_id')->default_renewal_cycle);
        $this->assertSame(DocumentLevel::Person, $pack->documentType('labour_card')->level);
    }

    public function test_seeding_is_idempotent(): void
    {
        $this->seed(UaeRulePackSeeder::class);
        $this->seed(UaeRulePackSeeder::class);

        $this->assertSame(1, RulePack::query()->where('country', 'AE')->count());
        $this->assertSame(14, RulePack::activeFor('AE')->documentTypes()->count());
    }
}
