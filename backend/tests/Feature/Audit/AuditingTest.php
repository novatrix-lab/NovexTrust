<?php

declare(strict_types=1);

namespace Tests\Feature\Audit;

use App\Enums\UserRole;
use App\Models\Entity;
use App\Models\Tenant;
use App\Models\User;
use App\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuditingTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = Tenant::factory()->create();
        $this->owner = User::factory()->for($this->tenant)->create(['role' => UserRole::AgencyOwner]);
        $this->actingAs($this->owner);
        app(TenantContext::class)->set($this->tenant->id);
    }

    public function test_model_mutations_are_audited(): void
    {
        $entity = Entity::factory()->for($this->tenant)->create();
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'entity.created',
            'target_type' => Entity::class,
            'target_id' => $entity->id,
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->owner->id,
        ]);

        $entity->update(['legal_name' => 'Renamed LLC']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'entity.updated', 'target_id' => $entity->id]);

        $entity->delete();
        $this->assertDatabaseHas('audit_logs', ['action' => 'entity.deleted', 'target_id' => $entity->id]);
    }

    public function test_audit_log_endpoint_is_owner_only_and_tenant_scoped(): void
    {
        // An action in another tenant.
        $other = Tenant::factory()->create();
        app(TenantContext::class)->set($other->id);
        Entity::factory()->for($other)->create();
        app(TenantContext::class)->set($this->tenant->id);

        // Owner sees only their tenant's trail.
        Sanctum::actingAs($this->owner);
        $this->getJson('/api/audit-logs')
            ->assertOk()
            ->assertJsonMissing(['tenant_id' => $other->id]);

        // Staff cannot access it.
        Sanctum::actingAs(User::factory()->for($this->tenant)->create(['role' => UserRole::AgencyStaff]));
        $this->getJson('/api/audit-logs')->assertForbidden();
    }
}
