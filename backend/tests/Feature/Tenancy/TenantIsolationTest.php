<?php

declare(strict_types=1);

namespace Tests\Feature\Tenancy;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * The core security property of the platform (SPEC.md §10, CLAUDE.md quality
 * bar): a tenant must never be able to read another tenant's data.
 */
class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_index_returns_only_the_callers_tenant(): void
    {
        $tenantA = Tenant::factory()->create();
        $owner = User::factory()->for($tenantA)->create();
        User::factory()->for($tenantA)->count(2)->create();

        $tenantB = Tenant::factory()->create();
        User::factory()->for($tenantB)->count(3)->create();

        Sanctum::actingAs($owner);

        $response = $this->getJson('/api/users')->assertOk();

        // 3 users in tenant A (owner + 2), none from tenant B.
        $response->assertJsonCount(3, 'data');

        $returnedTenantIds = collect($response->json('data'))->pluck('tenant_id')->unique();
        $this->assertEquals([$tenantA->id], $returnedTenantIds->values()->all());
    }

    public function test_user_cannot_read_a_user_from_another_tenant(): void
    {
        $tenantA = Tenant::factory()->create();
        $caller = User::factory()->for($tenantA)->create();

        $tenantB = Tenant::factory()->create();
        $foreignUser = User::factory()->for($tenantB)->create();

        Sanctum::actingAs($caller);

        // A cross-tenant id must 404, never leak the other tenant's record.
        $this->getJson("/api/users/{$foreignUser->id}")->assertNotFound();

        // Same-tenant lookup still works.
        $this->getJson("/api/users/{$caller->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $caller->id);
    }

    public function test_platform_admin_sees_all_tenants(): void
    {
        User::factory()->for(Tenant::factory())->count(2)->create();
        User::factory()->for(Tenant::factory())->count(2)->create();
        $admin = User::factory()->platformAdmin()->create();

        Sanctum::actingAs($admin);

        // 4 tenant users + the admin = 5, across all tenants (scope inactive).
        $this->getJson('/api/users')->assertOk()->assertJsonCount(5, 'data');
    }
}
