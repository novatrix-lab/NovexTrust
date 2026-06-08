<?php

declare(strict_types=1);

namespace Tests\Feature\Compliance;

use App\Enums\JurisdictionType;
use App\Models\DocumentType;
use App\Models\RulePack;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Per-jurisdiction rule variation is expressed in data: a jurisdiction-specific
 * document type overrides the generic ("any") one (SPEC.md §7).
 */
class RulePackResolverTest extends TestCase
{
    use RefreshDatabase;

    public function test_resolves_jurisdiction_specific_type_over_generic(): void
    {
        $pack = RulePack::factory()->create();
        DocumentType::factory()->for($pack)->create(['code' => 'trade_licence', 'jurisdiction' => null, 'default_lead_days' => 30]);
        DocumentType::factory()->for($pack)->create(['code' => 'trade_licence', 'jurisdiction' => JurisdictionType::Freezone, 'default_lead_days' => 60]);

        $this->assertSame(60, $pack->documentType('trade_licence', JurisdictionType::Freezone)->default_lead_days);
    }

    public function test_falls_back_to_generic_when_no_jurisdiction_match(): void
    {
        $pack = RulePack::factory()->create();
        DocumentType::factory()->for($pack)->create(['code' => 'trade_licence', 'jurisdiction' => null, 'default_lead_days' => 30]);
        DocumentType::factory()->for($pack)->create(['code' => 'trade_licence', 'jurisdiction' => JurisdictionType::Freezone, 'default_lead_days' => 60]);

        // Mainland has no specific variant → generic (30) applies.
        $this->assertSame(30, $pack->documentType('trade_licence', JurisdictionType::Mainland)->default_lead_days);
        $this->assertSame(30, $pack->documentType('trade_licence')->default_lead_days);
    }

    public function test_returns_null_for_unknown_code(): void
    {
        $pack = RulePack::factory()->create();

        $this->assertNull($pack->documentType('does_not_exist'));
    }
}
