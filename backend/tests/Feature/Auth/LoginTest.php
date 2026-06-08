<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_with_valid_credentials(): void
    {
        User::factory()->create([
            'email' => 'user@test.test',
            'password' => Hash::make('secret-pass'),
        ]);

        $this->postJson('/api/login', [
            'email' => 'user@test.test',
            'password' => 'secret-pass',
        ])->assertOk()->assertJsonStructure(['token', 'user' => ['id', 'email']]);
    }

    public function test_login_fails_with_wrong_password(): void
    {
        User::factory()->create([
            'email' => 'user@test.test',
            'password' => Hash::make('secret-pass'),
        ]);

        $this->postJson('/api/login', [
            'email' => 'user@test.test',
            'password' => 'wrong',
        ])->assertUnprocessable()->assertJsonValidationErrorFor('email');
    }

    public function test_login_fails_for_unknown_email(): void
    {
        $this->postJson('/api/login', [
            'email' => 'nobody@test.test',
            'password' => 'whatever',
        ])->assertUnprocessable()->assertJsonValidationErrorFor('email');
    }

    public function test_authenticated_user_can_fetch_self(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $this->getJson('/api/user')
            ->assertOk()
            ->assertJsonPath('id', $user->id)
            ->assertJsonPath('tenant_id', $user->tenant_id);
    }

    public function test_guest_cannot_fetch_self(): void
    {
        $this->getJson('/api/user')->assertUnauthorized();
    }

    public function test_user_can_logout_and_revoke_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('api')->plainTextToken;

        $this->withToken($token)->postJson('/api/logout')->assertOk();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }
}
