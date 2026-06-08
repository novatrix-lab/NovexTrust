<?php

declare(strict_types=1);

namespace Tests\Feature\Consents;

use App\Models\Consent;
use App\Models\Tenant;
use App\Models\User;
use App\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ConsentTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_records_onboarding_consents(): void
    {
        $this->postJson('/api/register', [
            'name' => 'Aisha',
            'email' => 'aisha@agency.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'tenant_name' => 'Falcon PRO',
            'tenant_type' => 'agency',
        ])->assertCreated();

        $user = User::withoutTenancy()->where('email', 'aisha@agency.test')->firstOrFail();

        $this->assertSame(3, Consent::withoutTenancy()->where('tenant_id', $user->tenant_id)->count());
        $this->assertDatabaseHas('consents', [
            'tenant_id' => $user->tenant_id,
            'purpose' => 'cross_border_transfer',
            'transfer_basis' => 'SCCs',
        ]);
    }

    public function test_consents_endpoint_is_tenant_scoped(): void
    {
        $tenantA = Tenant::factory()->create();
        $userA = User::factory()->for($tenantA)->create();
        app(TenantContext::class)->set($tenantA->id);
        Consent::create(['tenant_id' => $tenantA->id, 'user_id' => $userA->id, 'purpose' => 'mine', 'granted_at' => now()]);

        $tenantB = Tenant::factory()->create();
        app(TenantContext::class)->set($tenantB->id);
        Consent::create(['tenant_id' => $tenantB->id, 'purpose' => 'theirs', 'granted_at' => now()]);

        Sanctum::actingAs($userA);
        $this->getJson('/api/consents')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.purpose', 'mine');
    }
}
