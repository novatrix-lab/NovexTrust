<?php

declare(strict_types=1);

namespace Tests\Feature\Tenancy;

use App\Enums\UserRole;
use App\Models\Tenant;
use App\Models\User;
use App\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unit-level checks of the tenancy mechanics (scope filtering + auto-stamping)
 * directly against the model, without going through the HTTP layer.
 */
class TenantScopeTest extends TestCase
{
    use RefreshDatabase;

    private function context(): TenantContext
    {
        return app(TenantContext::class);
    }

    public function test_queries_are_constrained_to_the_current_tenant(): void
    {
        $tenantA = Tenant::factory()->create();
        User::factory()->for($tenantA)->count(2)->create();

        $tenantB = Tenant::factory()->create();
        User::factory()->for($tenantB)->count(5)->create();

        $this->context()->set($tenantA->id);

        $this->assertSame(2, User::query()->count());
    }

    public function test_without_tenant_context_no_filtering_applies(): void
    {
        User::factory()->for(Tenant::factory())->count(2)->create();
        User::factory()->for(Tenant::factory())->count(3)->create();

        // No context set.
        $this->assertSame(5, User::query()->count());
    }

    public function test_without_tenancy_scope_can_be_bypassed_explicitly(): void
    {
        $tenantA = Tenant::factory()->create();
        User::factory()->for($tenantA)->count(2)->create();
        User::factory()->for(Tenant::factory())->count(4)->create();

        $this->context()->set($tenantA->id);

        $this->assertSame(2, User::query()->count());
        $this->assertSame(6, User::withoutTenancy()->count());
    }

    public function test_run_without_temporarily_disables_scoping(): void
    {
        $tenantA = Tenant::factory()->create();
        User::factory()->for($tenantA)->count(1)->create();
        User::factory()->for(Tenant::factory())->count(3)->create();

        $this->context()->set($tenantA->id);

        $all = $this->context()->runWithout(fn () => User::query()->count());

        $this->assertSame(4, $all);
        // Context is restored afterwards.
        $this->assertSame(1, User::query()->count());
    }

    public function test_tenant_id_is_auto_stamped_on_create(): void
    {
        $tenant = Tenant::factory()->create();
        $this->context()->set($tenant->id);

        // No tenant_id provided — the trait stamps it from context.
        $user = User::create([
            'name' => 'Auto Stamped',
            'email' => 'auto@stamp.test',
            'password' => 'password123',
            'role' => UserRole::AgencyStaff,
        ]);

        $this->assertSame($tenant->id, $user->tenant_id);
    }
}
