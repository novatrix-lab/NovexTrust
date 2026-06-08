<?php

declare(strict_types=1);

namespace Tests\Feature\Domain;

use App\Models\Deadline;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\Entity;
use App\Models\RulePack;
use App\Models\Tenant;
use App\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Confirms tenancy carries through the whole domain model: tenant-owned models
 * are isolated; the global rule-pack models are not.
 */
class DomainTenantScopingTest extends TestCase
{
    use RefreshDatabase;

    private function setTenant(Tenant $tenant): void
    {
        app(TenantContext::class)->set($tenant->id);
    }

    public function test_tenant_owned_models_are_scoped(): void
    {
        $a = Tenant::factory()->create();
        $b = Tenant::factory()->create();

        Entity::factory()->for($a)->count(2)->create();
        Entity::factory()->for($b)->count(3)->create();

        $this->setTenant($a);

        $this->assertSame(2, Entity::query()->count());
        $this->assertSame(5, Entity::withoutTenancy()->count());
    }

    public function test_deadlines_are_scoped_via_denormalised_tenant_id(): void
    {
        $a = Tenant::factory()->create();
        $b = Tenant::factory()->create();

        Deadline::factory()->forDocument(Document::factory()->forEntity(Entity::factory()->for($a)->create())->create())->create();
        Deadline::factory()->count(2)->forDocument(Document::factory()->forEntity(Entity::factory()->for($b)->create())->create())->create();

        $this->setTenant($a);

        $this->assertSame(1, Deadline::query()->count());
        $this->assertSame(3, Deadline::withoutTenancy()->count());
    }

    public function test_rule_pack_models_are_global_and_not_scoped(): void
    {
        RulePack::factory()->count(2)->create();
        DocumentType::factory()->count(3)->create(); // creates 3 more packs

        $this->setTenant(Tenant::factory()->create());

        // Unaffected by tenant context — shared across all tenants.
        $this->assertSame(5, RulePack::query()->count());
        $this->assertSame(3, DocumentType::query()->count());
    }

    public function test_tenant_id_is_auto_stamped_on_domain_models(): void
    {
        $tenant = Tenant::factory()->create();
        $this->setTenant($tenant);

        $entity = Entity::create(['legal_name' => 'Auto Stamped LLC']);

        $this->assertSame($tenant->id, $entity->tenant_id);
    }

    public function test_deleting_a_tenant_cascades_to_its_domain_records(): void
    {
        $tenant = Tenant::factory()->create();
        $entity = Entity::factory()->for($tenant)->create();
        $document = Document::factory()->forEntity($entity)->create();
        Deadline::factory()->forDocument($document)->create();

        $this->assertSame(1, Entity::withoutTenancy()->count());
        $this->assertSame(1, Document::withoutTenancy()->count());
        $this->assertSame(1, Deadline::withoutTenancy()->count());

        $tenant->delete();

        $this->assertSame(0, Entity::withoutTenancy()->count());
        $this->assertSame(0, Document::withoutTenancy()->count());
        $this->assertSame(0, Deadline::withoutTenancy()->count());
    }
}
