<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_creates_tenant_and_owner_and_returns_token(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Aisha Owner',
            'email' => 'aisha@agency.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'tenant_name' => 'Falcon PRO Services',
            'tenant_type' => 'agency',
        ]);

        $response->assertCreated()
            ->assertJsonStructure(['token', 'user' => ['id', 'tenant_id', 'name', 'email', 'role']])
            ->assertJsonPath('user.role', UserRole::AgencyOwner->value);

        $this->assertDatabaseHas('tenants', [
            'name' => 'Falcon PRO Services',
            'type' => 'agency',
            'country' => 'AE',
        ]);

        $tenant = Tenant::firstOrFail();
        $this->assertDatabaseHas('users', [
            'email' => 'aisha@agency.test',
            'tenant_id' => $tenant->id,
            'role' => UserRole::AgencyOwner->value,
        ]);

        // Password is hashed, never stored in plaintext.
        $this->assertNotSame('password123', User::withoutTenancy()->first()->password);
    }

    public function test_sme_registration_assigns_sme_owner_role(): void
    {
        $this->postJson('/api/register', [
            'name' => 'Sam SME',
            'email' => 'sam@sme.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'tenant_name' => 'Corner Shop LLC',
            'tenant_type' => 'sme',
        ])->assertCreated()->assertJsonPath('user.role', UserRole::SmeOwner->value);
    }

    public function test_registration_rejects_duplicate_email(): void
    {
        User::factory()->create(['email' => 'dupe@test.test']);

        $this->postJson('/api/register', [
            'name' => 'Dupe',
            'email' => 'dupe@test.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'tenant_name' => 'Whatever',
            'tenant_type' => 'agency',
        ])->assertUnprocessable()->assertJsonValidationErrorFor('email');
    }

    public function test_registration_rejects_invalid_tenant_type(): void
    {
        $this->postJson('/api/register', [
            'name' => 'Bad Type',
            'email' => 'bad@type.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'tenant_name' => 'Whatever',
            'tenant_type' => 'charity',
        ])->assertUnprocessable()->assertJsonValidationErrorFor('tenant_type');
    }
}
