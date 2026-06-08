<?php

declare(strict_types=1);

namespace Tests\Feature\Imports;

use App\Enums\JurisdictionType;
use App\Imports\EntityCsvImporter;
use App\Models\Entity;
use App\Models\Tenant;
use App\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EntityCsvImporterTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = Tenant::factory()->create();
        app(TenantContext::class)->set($this->tenant->id);
    }

    public function test_imports_valid_rows_and_skips_invalid(): void
    {
        $csv = implode("\n", [
            'legal_name,trade_name,jurisdiction_type,authority,license_number',
            'Falcon LLC,Falcon,freezone,DMCC,CN-1',
            ',No Name Co,mainland,DED,CN-2',          // missing legal_name → skipped
            'Corner Shop LLC,Corner,mainland,DED,CN-3',
        ]);

        $result = $this->app->make(EntityCsvImporter::class)->import($csv);

        $this->assertSame(2, $result['imported']);
        $this->assertSame(1, $result['skipped']);
        $this->assertCount(1, $result['errors']);

        $falcon = Entity::query()->where('legal_name', 'Falcon LLC')->firstOrFail();
        $this->assertSame(JurisdictionType::Freezone, $falcon->jurisdiction_type);
        $this->assertSame($this->tenant->id, $falcon->tenant_id);
    }

    public function test_invalid_jurisdiction_becomes_null(): void
    {
        $csv = "legal_name,jurisdiction_type\nMystery LLC,offshore";

        $this->app->make(EntityCsvImporter::class)->import($csv);

        $this->assertNull(Entity::query()->where('legal_name', 'Mystery LLC')->firstOrFail()->jurisdiction_type);
    }

    public function test_imported_entities_are_tenant_scoped(): void
    {
        $this->app->make(EntityCsvImporter::class)->import("legal_name\nScoped LLC");

        // Visible to this tenant, not to others.
        $this->assertSame(1, Entity::query()->count());
        app(TenantContext::class)->set(Tenant::factory()->create()->id);
        $this->assertSame(0, Entity::query()->count());
    }
}
